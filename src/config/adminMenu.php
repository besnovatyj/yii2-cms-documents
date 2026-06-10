<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [
    // Files
    [
        'label' => 'Files',
        'iconClass' => 'bi bi-files me-1',
        'url' => ['/Documents/backend/document/index'],
        'active' => static function () {
            return str_contains(\Yii::$app->request->url, 'Documents/backend/document');
        },
        '_meta' => [
            'placements' => [
                [
                    'location' => 'left-sidebar',
                    'group' => 'Documents',
                    'groupIcon' => 'bi bi-files',
                    'priority' => 100,
                    'groupPriority' => 100,
                ],
            ],
        ],
    ],
    // Categories
    [
        'label' => 'Categories',
        'iconClass' => 'bi bi-list-ol me-1',
        'url' => ['/Documents/backend/category/index'],
        'active' => static function () {
            return str_contains(\Yii::$app->request->url, 'Documents/backend/category');
        },
        '_meta' => [
            'placements' => [
                [
                    'location' => 'left-sidebar',
                    'group' => 'Documents',
                    'groupIcon' => 'bi bi-files',
                    'priority' => 100,
                    'groupPriority' => 100,
                ],
            ],
        ],
    ],
];
