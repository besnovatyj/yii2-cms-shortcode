<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\services;

use Besnovatyj\Shortcode\definitions\ReverseMode;
use Besnovatyj\Shortcode\definitions\ShortcodeDefinition;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\Module;
use Yii;
use yii\helpers\Url;
use yii\web\Application;

/** Определения, вычисляемые в контексте текущего приложения, а не в общем кэше. */
final class BuiltinShortcodeDefinitions
{
    /** Возвращает встроенные шорткоды установленного модуля. @return list<ShortcodeDefinition> */
    public function all(): array
    {
        if (Yii::$app->getModule(Module::moduleId()) === null) {
            return [];
        }
        $items = [
            new ShortcodeDefinition('%staticHost%', Shortcode::TYPE_TEXT, Yii::$app->params['staticHostName'], reverseMode: ReverseMode::UrlPrefix),
            new ShortcodeDefinition('%frontendHost%', Shortcode::TYPE_TEXT, Yii::$app->params['frontendHostName']),
        ];
        if (Yii::$app instanceof Application) {
            $items[] = new ShortcodeDefinition('%homeUrl%', Shortcode::TYPE_TEXT, Url::home());
        }
        return $items;
    }
}
