<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode;

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Besnovatyj\Kernel\module\CmsModule;
use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesComponents;
use Besnovatyj\Contracts\module\ProvidesMigrations;

class Module extends CmsModule implements
    DeclaresModule, ProvidesComponents,
    ProvidesMigrations
{
    public const bool EDITABLE = true;
    public const string MODULE_ID = 'Shortcode';

    /** Имя компонента приложения, под которым регистрируется {@see ShortcodeManager}. */
    public const string COMPONENT_ID = 'shortcode';

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function moduleConfig(): array { return require __DIR__.'/config/config.php'; }
    public static function migrationPath(): string { return __DIR__.'/migrations'; }
    public static function migrationNamespace(): ?string { return __NAMESPACE__.'\\migrations'; }
    public static function components(): array { return [self::COMPONENT_ID => ['class' => ShortcodeManager::class]]; }
}
