/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Поведение справочника шорткодов: фильтрация списка, копирование примеров вставки
 * и выделение примера по клику. Разметку отдаёт PHP, скрипт только оживляет её.
 */

/** Подписи, приходящие из PHP через `data-config` — чтобы тексты интерфейса жили в одном месте. */
interface WidgetConfig {
    readonly copiedLabel: string;
    readonly failedLabel: string;
}

/** Значение фильтра по типу: пустая строка — «все». */
type TypeFilter = string;

const MODAL_SELECTOR = '.shortcodes-list-modal';
const FEEDBACK_TIMEOUT_MS = 1600;

const DEFAULT_CONFIG: WidgetConfig = {
    copiedLabel: 'Скопировано',
    failedLabel: 'Не удалось',
};

/**
 * Копирование в буфер обмена. `navigator.clipboard` доступен только в защищённом контексте,
 * а админку разворачивают и по http — поэтому предусмотрен запасной путь через `execCommand`.
 */
const copyToClipboard = async (text: string): Promise<boolean> => {
    if (window.isSecureContext && navigator.clipboard !== undefined) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // Права на буфер могли не дать — падаем в запасной путь ниже.
        }
    }

    return legacyCopy(text);
};

/**
 * Запасное копирование через скрытое поле и `document.execCommand`.
 */
const legacyCopy = (text: string): boolean => {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.top = '0';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    }

    document.body.removeChild(area);

    return copied;
};

/**
 * Выделение содержимого элемента — чтобы пример можно было забрать вручную,
 * если копирование в буфер запрещено браузером.
 */
const selectContent = (element: HTMLElement): void => {
    const selection = window.getSelection();
    if (selection === null) {
        return;
    }

    const range = document.createRange();
    range.selectNodeContents(element);
    selection.removeAllRanges();
    selection.addRange(range);
};

class ShortcodesList {
    private readonly searchInput: HTMLInputElement | null;
    private readonly items: HTMLElement[];
    private readonly groups: HTMLElement[];
    private readonly emptyState: HTMLElement | null;
    private readonly feedbackTimers = new WeakMap<HTMLElement, number>();
    private typeFilter: TypeFilter = '';

    public constructor(
        private readonly root: HTMLElement,
        private readonly config: WidgetConfig,
    ) {
        this.searchInput = root.querySelector<HTMLInputElement>('[data-sc-search-input]');
        this.items = Array.from(root.querySelectorAll<HTMLElement>('[data-sc-item]'));
        this.groups = Array.from(root.querySelectorAll<HTMLElement>('[data-sc-group]'));
        this.emptyState = root.querySelector<HTMLElement>('[data-sc-empty]');

        this.detachFromContent();
        this.bind();
    }

    /**
     * Модалку переносим в `body`: виджет вставляют внутрь карточек и форм редактирования,
     * а там она рискует попасть под `overflow`/`z-index` контейнера и отправить форму по Enter
     * из поля поиска. В `body` окно ведёт себя предсказуемо независимо от места вызова виджета.
     */
    private detachFromContent(): void {
        if (this.root.parentElement !== document.body) {
            document.body.appendChild(this.root);
        }
    }

    private bind(): void {
        this.searchInput?.addEventListener('input', () => this.applyFilter());
        this.searchInput?.addEventListener('keydown', (event: KeyboardEvent) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

        this.root.querySelectorAll<HTMLInputElement>('[data-sc-type]').forEach((input) => {
            input.addEventListener('change', () => {
                this.typeFilter = input.value;
                this.applyFilter();
            });
        });

        this.root.addEventListener('click', (event: Event) => this.onClick(event));

        // Фокус в поиске сразу после открытия — типичный сценарий «пришёл за конкретным шорткодом».
        this.root.addEventListener('shown.bs.modal', () => this.searchInput?.focus());
    }

    private onClick(event: Event): void {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const copyButton = target.closest<HTMLElement>('[data-sc-copy]');
        if (copyButton !== null) {
            void this.copy(copyButton);
            return;
        }

        const selectable = target.closest<HTMLElement>('[data-sc-select]');
        if (selectable !== null) {
            selectContent(selectable);
        }
    }

    private async copy(button: HTMLElement): Promise<void> {
        const copied = await copyToClipboard(button.dataset.scCopy ?? '');
        this.showFeedback(button, copied);

        // Копирование не удалось — выделяем пример, чтобы его можно было забрать руками.
        if (!copied) {
            const example = button.closest('.sc-example')?.querySelector<HTMLElement>('[data-sc-select]');
            if (example != null) {
                selectContent(example);
            }
        }
    }

    /**
     * Временная подмена содержимого кнопки на результат операции с возвратом исходного вида.
     */
    private showFeedback(button: HTMLElement, copied: boolean): void {
        if (button.dataset.scOriginal === undefined) {
            button.dataset.scOriginal = button.innerHTML;
        }

        const icon = copied ? 'bi-check2' : 'bi-x-lg';
        const label = copied ? this.config.copiedLabel : this.config.failedLabel;
        button.innerHTML = `<i class="bi ${icon}"></i><span class="ms-1">${label}</span>`;

        const previous = this.feedbackTimers.get(button);
        if (previous !== undefined) {
            window.clearTimeout(previous);
        }

        this.feedbackTimers.set(button, window.setTimeout(() => {
            button.innerHTML = button.dataset.scOriginal ?? '';
            this.feedbackTimers.delete(button);
        }, FEEDBACK_TIMEOUT_MS));
    }

    /**
     * Применение поиска и фильтра по типу: прячем не подошедшие карточки, пустые группы
     * и показываем заглушку, если не осталось ничего.
     */
    private applyFilter(): void {
        const query = (this.searchInput?.value ?? '').trim().toLowerCase();
        let visible = 0;

        for (const item of this.items) {
            const matchesType = this.typeFilter === '' || item.dataset.scItemType === this.typeFilter;
            const matchesQuery = query === '' || (item.dataset.scSearch ?? '').includes(query);
            const show = matchesType && matchesQuery;

            item.classList.toggle('d-none', !show);
            if (show) {
                visible += 1;
            }
        }

        for (const group of this.groups) {
            const shown = group.querySelectorAll('[data-sc-item]:not(.d-none)').length;
            group.classList.toggle('d-none', shown === 0);

            const counter = group.querySelector<HTMLElement>('[data-sc-group-count]');
            if (counter !== null) {
                counter.textContent = String(shown);
            }
        }

        this.emptyState?.classList.toggle('d-none', visible > 0);
    }
}

/**
 * Чтение конфига из `data-config`. Битый JSON не должен ронять виджет, но и молчать о нём нельзя.
 */
const readConfig = (root: HTMLElement): WidgetConfig => {
    const raw = root.dataset.config;
    if (raw === undefined || raw === '') {
        return DEFAULT_CONFIG;
    }

    try {
        return { ...DEFAULT_CONFIG, ...JSON.parse(raw) as Partial<WidgetConfig> };
    } catch (error) {
        console.warn('ShortcodesList: не удалось разобрать data-config, взяты значения по умолчанию', error);
        return DEFAULT_CONFIG;
    }
};

const init = (): void => {
    document.querySelectorAll<HTMLElement>(MODAL_SELECTOR).forEach((root) => {
        if (root.dataset.scReady === '1') {
            return;
        }
        root.dataset.scReady = '1';
        new ShortcodesList(root, readConfig(root));
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
