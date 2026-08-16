<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\widgets\shortcodesList\assets;

use yii\bootstrap5\BootstrapPluginAsset;
use yii\web\AssetBundle;

/**
 * Ассеты справочника шорткодов {@see \Besnovatyj\Shortcode\widgets\ShortcodesList}.
 *
 * Зависимость от {@see BootstrapPluginAsset} не декоративная: виджет — модалка, а наш скрипт
 * подписывается на событие `shown.bs.modal`, поэтому JS бандла обязан грузиться после Bootstrap.
 */
class Assets extends AssetBundle
{
    public $sourcePath = __DIR__ . '/../media/dist';

    public $css = [
        'index.css',
    ];

    public $js = [
        'index.js',
    ];

    public $depends = [
        BootstrapPluginAsset::class,
    ];
}
