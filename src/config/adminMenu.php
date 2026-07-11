<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [[
    'label' => 'Shortcodes',
    'iconClass' => 'bi bi-braces-asterisk me-1',
    'url' => ['/Shortcode/backend/default/index'],
    'active' => static function () {
        return str_contains(\Yii::$app->request->url, 'Shortcode/backend/default');
    },
    '_meta' => [
        'placements' => [
            [
                'location' => 'right-sidebar',
                'group' => 'Service',
                'groupIcon' => 'bi bi-sliders',
                'priority' => 100,
                'groupPriority' => 100,
            ],
        ],
    ],
]];
