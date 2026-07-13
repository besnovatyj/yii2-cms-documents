<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Documents\Module;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Объявляется через `extra.config-plugin`, собирается modman в merge-plan и мёржится в рантайме.
 * Содержит регистрацию модуля. Меню (adminMenu) и миграции остаются вкладами modman. Значения берутся
 * из статических методов {@see Module} — единый источник, без дублирования.
 *
 * URL-правила фронтенда — вклад в `frontendUrlManager` группы `common` (см. README_Yii2_Modules.md).
 * Перенесены из захардкоженного `frontend/config/url-manager.php`; первый сегмент роута капитализирован
 * под реальный id модуля 'Documents'. Гейтятся modman.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
            ['version' => Module::moduleVersion()],
        ),
    ],
    'components' => [
        'frontendUrlManager' => [
            'rules' => [
                'documents'                           => 'Documents/document/index',
                'documents/<id:\d+>'                  => 'Documents/document/actor',
                'documents/<slug:[\w\-]+>/<page:\d+>' => 'Documents/document/category', // <page> — пагинация
                'documents/<slug:[\w\-]+>'            => 'Documents/document/category',
            ],
        ],
    ],
];
