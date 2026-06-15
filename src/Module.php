<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode;

use Besnovatyj\Shortcode\components\ShortcodeManager;
use common\components\module\CmsModule;
use modules\modman\contract\DeclaresModule;
use modules\modman\contract\ProvidesComponents;
use modules\modman\contract\ProvidesMigrations;
use modules\modman\contract\ProvidesAdminMenu;
use Yii;
use yii\helpers\Url;

class Module extends CmsModule implements
    DeclaresModule, ProvidesComponents,
    ProvidesMigrations, ProvidesAdminMenu
{
    public const bool EDITABLE = true;
    public const string VERSION = '1.0.0';
    public const string MODULE_ID = 'Shortcode';
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

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function moduleVersion(): string { return self::VERSION; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function adminMenu(): array       { return require __DIR__.'/config/adminMenu.php'; }
    public static function moduleConfig(): array { return require __DIR__.'/config/config.php'; }
    public static function migrationPath(): string       { return __DIR__.'/migrations'; }
    public static function migrationNamespace(): ?string { return __NAMESPACE__.'\\migrations'; }
    public static function components(): array      { return ['shortcode' => ['class' => ShortcodeManager::class]]; }
}
