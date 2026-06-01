<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode;

use Besnovatyj\Shortcode\components\ShortcodeManager;
use common\components\module\BaseModule;
use Yii;
use yii\helpers\Url;

class Module extends BaseModule
{
    public const true EDITABLE = true;

    public function init(): void
    {
        parent::init();
        $this->params = [
            // TODO зададим здесь глобальные шорткоды, которые будем использовать во всём приложении.
            //  Или в настройках компонента, ниже, в методе `getComponentsConfig()`?
            'replaceParams' => [
                '%homeUrl%' => Url::home(),
                '%staticHost%' => Yii::$app->params['staticHostName'],
                '%frontendHost%' => Yii::$app->params['frontendHostName'],
            ],
        ];
    }

    public static function getAdminMenu(): array
    {
        return require __DIR__ . '/config/adminMenu.php';
    }

    public static function getConfig(): array
    {
        return require __DIR__ . '/config/config.php';
    }

    public static function getOptions(): array
    {
        return require __DIR__ . '/config/options.php';
    }

    public static function getDependencies(): array
    {
        return require __DIR__ . '/config/dependencies.php';
    }

    public static function getComponentsConfig(): array
    {
        return [
            'shortcode' => [
                'class' => ShortcodeManager::class,
//                'cacheId' => 'cacheIdTest',
//                'items' => ['qwerty-1', 'qwerty-2'],
            ],
        ];
    }

}
