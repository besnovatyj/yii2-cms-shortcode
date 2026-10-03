<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Besnovatyj\Shortcode\Module;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Объявляется через `extra.config-plugin`, собирается modman в merge-plan и мёржится в рантайме.
 * Содержит регистрацию модуля и его компоненты.
 * Меню админки — `adminMenu.php` (группа `admin-menu`), миграции — вклад modman.
 * Значения берутся из статических методов {@see Module} — единый источник, без дублирования.
 *
 * NB: биндинг `container.definitions[ShortcodeManager::class] => Yii::$app->get('shortcode')` сюда
 * добавлять НЕЛЬЗЯ — получается бесконечная рекурсия: ServiceLocator резолвит компонент через
 * `Yii::createObject(['class' => ShortcodeManager::class])`, то есть через тот же контейнер, и
 * определение вызывает само себя. Цена второго экземпляра менеджера снята иначе — общим кэшем
 * каталога шорткодов ({@see \Besnovatyj\Shortcode\services\ShortcodeCatalog}), поэтому лишней
 * работы с БД он не делает.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
        ),
    ],
    'components' => Module::components(),
];
