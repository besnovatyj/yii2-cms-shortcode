/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 *
 * ВНИМАНИЕ: это собранный артефакт. Источник — ../src/index.ts,
 * пересборка: npm run build (в каталоге media виджета).
 */
(() => {
    'use strict';

    const MODAL_SELECTOR = '.shortcodes-list-modal';
    const FEEDBACK_TIMEOUT_MS = 1600;

    const DEFAULT_CONFIG = {
        copiedLabel: 'Скопировано',
        failedLabel: 'Не удалось',
    };

    const legacyCopy = (text) => {
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

    const copyToClipboard = async (text) => {
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

    const selectContent = (element) => {
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
        constructor(root, config) {
            this.root = root;
            this.config = config;
            this.feedbackTimers = new WeakMap();
            this.typeFilter = '';

            this.searchInput = root.querySelector('[data-sc-search-input]');
            this.items = Array.from(root.querySelectorAll('[data-sc-item]'));
            this.groups = Array.from(root.querySelectorAll('[data-sc-group]'));
            this.emptyState = root.querySelector('[data-sc-empty]');

            this.detachFromContent();
            this.bind();
        }

        detachFromContent() {
            if (this.root.parentElement !== document.body) {
                document.body.appendChild(this.root);
            }
        }

        bind() {
            this.searchInput?.addEventListener('input', () => this.applyFilter());
            this.searchInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            this.root.querySelectorAll('[data-sc-type]').forEach((input) => {
                input.addEventListener('change', () => {
                    this.typeFilter = input.value;
                    this.applyFilter();
                });
            });

            this.root.addEventListener('click', (event) => this.onClick(event));
            this.root.addEventListener('shown.bs.modal', () => this.searchInput?.focus());
        }

        onClick(event) {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const copyButton = target.closest('[data-sc-copy]');
            if (copyButton !== null) {
                void this.copy(copyButton);
                return;
            }

            const selectable = target.closest('[data-sc-select]');
            if (selectable !== null) {
                selectContent(selectable);
            }
        }

        async copy(button) {
            const copied = await copyToClipboard(button.dataset.scCopy ?? '');
            this.showFeedback(button, copied);

            if (!copied) {
                const example = button.closest('.sc-example')?.querySelector('[data-sc-select]');
                if (example != null) {
                    selectContent(example);
                }
            }
        }

        showFeedback(button, copied) {
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

        applyFilter() {
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

                const counter = group.querySelector('[data-sc-group-count]');
                if (counter !== null) {
                    counter.textContent = String(shown);
                }
            }

            this.emptyState?.classList.toggle('d-none', visible > 0);
        }
    }

    const readConfig = (root) => {
        const raw = root.dataset.config;
        if (raw === undefined || raw === '') {
            return DEFAULT_CONFIG;
        }

        try {
            return { ...DEFAULT_CONFIG, ...JSON.parse(raw) };
        } catch (error) {
            console.warn('ShortcodesList: не удалось разобрать data-config, взяты значения по умолчанию', error);
            return DEFAULT_CONFIG;
        }
    };

    const init = () => {
        document.querySelectorAll(MODAL_SELECTOR).forEach((root) => {
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
})();
