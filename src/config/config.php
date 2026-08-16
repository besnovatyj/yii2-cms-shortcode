<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [
    'id' => 'Shortcode',
    'params' => [
        'iconClass' => 'bi bi-braces-asterisk',

        'directories' => false, // Если для работы модуля необходимы директории для статики

        // Интеграция с модулем очистки (ClearManager): сброс кэша каталога шорткодов.
        // Читается EndpointCollectorService, если ClearManager установлен; иначе параметры инертны
        // (жёсткой зависимости нет). См. controllers/backend/ClearController.
        'endpoints' => [
            'clear' => [
                'catalog' => [
                    'rowTitle' => 'Кэш каталога шорткодов',
                    'getData' => '/Shortcode/backend/clear/get-data',
                    'clear' => '/Shortcode/backend/clear/clear-data',
                ],
            ],
        ],
    ],
];
