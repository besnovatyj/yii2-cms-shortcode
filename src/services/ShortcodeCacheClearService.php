<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\services;

/**
 * Интеграция с модулем очистки (ClearManager): сброс кэша каталога шорткодов.
 *
 * У модуля нет файлового кеша — «очищаемый ресурс» это кэш каталога ({@see ShortcodeCatalog},
 * тег {@see ShortcodeCatalog::CACHE_TAG}). Сброс форсирует перечитывание шорткодов из БД при
 * следующем обращении. Формат методов повторяет {@see \Besnovatyj\RouteAlias\services\AliasCacheClearService}
 * (строка для `getData`, bool для `clearData`).
 */
final class ShortcodeCacheClearService
{
    public function __construct(private readonly ShortcodeCatalog $catalog)
    {
    }

    /**
     * Человекочитаемое состояние кэша: сколько шорткодов сейчас в каталоге.
     */
    public function getData(): string
    {
        $count = $this->catalog->count();
        return $count . ' ' . $this->pluralShortcodes($count) . ' (кэш каталога)';
    }

    /**
     * Сбрасывает кэш каталога. Пустой/несконфигурированный кэш — не ошибка.
     */
    public function clearData(): bool
    {
        $this->catalog->invalidate();
        return true;
    }

    private function pluralShortcodes(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return 'шорткод';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 'шорткода';
        }
        return 'шорткодов';
    }
}
