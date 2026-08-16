<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\services;

use Besnovatyj\Shortcode\repositories\ShortcodeRepository;
use Yii;
use yii\caching\TagDependency;

/**
 * Каталог шорткодов из БД — единый кэшируемый источник для всех потребителей модуля.
 *
 * Набор шорткодов маленький и меняется редко, а читается на каждый рендер контента
 * ({@see \Besnovatyj\Shortcode\components\ShortcodeManager} поднимается и на фронте) и на каждый показ
 * справочника, поэтому грузится целиком одним элементом кэша (APCu) с тегом {@see self::CACHE_TAG}.
 * Инвалидация — «крупным помолом», из {@see ShortcodeManageService} при любой правке шорткода.
 *
 * Строки отдаются массивами, а не AR-объектами: так они кладутся в кэш без сериализации моделей,
 * а сборка нужного представления остаётся за потребителем (карты замен для менеджера,
 * {@see \Besnovatyj\Shortcode\readModels\ShortcodeItem} для справочника).
 */
final class ShortcodeCatalog
{
    public const string CACHE_KEY = 'shortcode.catalog';
    public const string CACHE_TAG = 'shortcodes';

    public function __construct(private readonly ShortcodeRepository $repository)
    {
    }

    /**
     * Все шорткоды: сначала по типу, внутри — по имени.
     *
     * @return list<array{id:int|string,shortcode:string,type:string,replacement:string,description:?string,example:?string}>
     */
    public function rows(): array
    {
        // Кэш может быть не сконфигурирован (например, в консоли) — тогда читаем напрямую:
        // корректность важнее скорости.
        if (!Yii::$app->has('cache') || Yii::$app->cache === null) {
            return $this->repository->findAllRows();
        }

        return Yii::$app->cache->getOrSet(
            self::CACHE_KEY,
            fn(): array => $this->repository->findAllRows(),
            null,
            new TagDependency(['tags' => [self::CACHE_TAG]]),
        );
    }

    /**
     * Карта `шорткод => замена` для одного типа — в таком виде каталог нужен менеджеру.
     *
     * @return array<string, string>
     */
    public function replacements(string $type): array
    {
        $map = [];
        foreach ($this->rows() as $row) {
            if ($row['type'] === $type) {
                $map[(string)$row['shortcode']] = (string)$row['replacement'];
            }
        }

        return $map;
    }

    /**
     * Сколько шорткодов сейчас в каталоге (для справки в модуле очистки).
     */
    public function count(): int
    {
        return count($this->rows());
    }

    /**
     * Сброс кэша каталога. Отсутствие кэша — не ошибка.
     */
    public function invalidate(): void
    {
        if (Yii::$app->has('cache') && Yii::$app->cache !== null) {
            TagDependency::invalidate(Yii::$app->cache, [self::CACHE_TAG]);
        }
    }
}
