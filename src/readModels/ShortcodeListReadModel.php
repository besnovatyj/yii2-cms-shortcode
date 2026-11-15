<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\readModels;

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\services\ShortcodeCatalog;
use Besnovatyj\Shortcode\services\ShortcodeRegistry;

/**
 * Сводный список всех доступных приложению шорткодов для справочных интерфейсов.
 *
 * Источников два, и они дополняют друг друга:
 *  - каталог {@see ShortcodeCatalog} (кэшированное чтение БД) — даёт описание, пример вставки и `id`
 *    для ссылки на карточку;
 *  - {@see ShortcodeManager} — даёт шорткоды, зарегистрированные в рантайме (`%homeUrl%`, `%staticHost%`
 *    и всё, что модули регистрируют кодом). У них нет карточки в админке, но в подсказке они нужны.
 *
 * При совпадении имени побеждает запись из БД: она содержательнее (описание + пример).
 */
final class ShortcodeListReadModel
{
    /** Использует общий эффективный набор, а не собственную логику приоритетов. */
    public function __construct(
        private readonly ShortcodeRegistry $registry,
    ) {
    }

    /**
     * Все шорткоды: сначала виджетные, затем текстовые, внутри группы — по алфавиту.
     *
     * @return ShortcodeItem[]
     */
    public function all(): array
    {
        // Тот же guard, что и в ShortcodeManager::init(): без зарегистрированного модуля таблицы
        // может не быть (виджет-подсказку способен вызвать сторонний модуль).
        // Guard и приоритет источников теперь централизованы в реестре.
        $items = array_map(ShortcodeItem::fromDefinition(...), array_values($this->registry->all()));
        usort($items, static fn (ShortcodeItem $a, ShortcodeItem $b): int => [
            $a->isWidget() ? 0 : 1, mb_strtolower($a->shortcode),
        ] <=> [$b->isWidget() ? 0 : 1, mb_strtolower($b->shortcode)]);
        return $items;
    }

    /**
     * То же самое, но разложенное по типам — в таком виде это нужно представлению.
     *
     * @return array{widget: ShortcodeItem[], text: ShortcodeItem[]}
     */
    public function grouped(): array
    {
        $groups = [Shortcode::TYPE_WIDGET => [], Shortcode::TYPE_TEXT => []];

        foreach ($this->all() as $item) {
            $groups[$item->isWidget() ? Shortcode::TYPE_WIDGET : Shortcode::TYPE_TEXT][] = $item;
        }

        return $groups;
    }

}
