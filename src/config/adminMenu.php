<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Contracts\adminMenu\AdminMenuLocation;
use Besnovatyj\Contracts\adminMenu\AdminMenuPlacement;

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
                new AdminMenuPlacement(
                    location: AdminMenuLocation::LeftSidebar,
                    group: 'Documents',
                    groupIcon: 'bi bi-files',
                    groupPriority: 100,
                    priority: 100,
                ),
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
                new AdminMenuPlacement(
                    location: AdminMenuLocation::LeftSidebar,
                    group: 'Documents',
                    groupIcon: 'bi bi-files',
                    groupPriority: 100,
                    priority: 100,
                ),
            ],
        ],
    ],
];
