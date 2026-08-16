<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Besnovatyj\Shortcode\Module;
use yii\base\InvalidConfigException;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Объявляется через `extra.config-plugin`, собирается modman в merge-plan и мёржится в рантайме.
 * Содержит регистрацию модуля и его компоненты. Меню (adminMenu) и миграции остаются вкладами modman.
 * Значения берутся из статических методов {@see Module} — единый источник, без дублирования.
 *
 * Дополнительно вкладывает DI-биндинг типа {@see ShortcodeManager} на компонент приложения
 * `shortcode`. Без него контейнер, встретив тип в конструкторе (виджеты, read-модели), собрал бы
 * ВТОРОЙ экземпляр менеджера: лишний запрос к БД на каждый рендер и, что важнее, потеря шорткодов,
 * которые другие модули регистрируют в рантайме на компоненте приложения. Биндинг ленивый —
 * компонент поднимается только когда его действительно попросят.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
            ['version' => Module::moduleVersion()],
        ),
    ],
    'container' => [
        'definitions' => [
            ShortcodeManager::class => static function (): ShortcodeManager {
                $component = Yii::$app->get(Module::COMPONENT_ID);
                if (!$component instanceof ShortcodeManager) {
                    throw new InvalidConfigException(sprintf(
                        'Компонент приложения "%s" должен быть экземпляром %s.',
                        Module::COMPONENT_ID,
                        ShortcodeManager::class,
                    ));
                }

                return $component;
            },
        ],
    ],
    'components' => Module::components(),
];
