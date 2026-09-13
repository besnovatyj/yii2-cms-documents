<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Documents\Module;
use Besnovatyj\Validators\SlugValidator;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Объявляется через `extra.config-plugin`, собирается modman в merge-plan и мёржится в рантайме.
 * Содержит регистрацию модуля. Меню (adminMenu) и миграции остаются вкладами modman. Значения берутся
 * из статических методов {@see Module} — единый источник, без дублирования.
 *
 * URL-правила фронтенда — вклад в `frontendUrlManager` группы `common` (компонент есть и во фронте, и в
 * бэкенде). Плоская грамматика, общая для контентных модулей: `<prefix>` — список, `<prefix>/<id:\d+>` —
 * материал (всегда число), `<prefix>/<slug>` — раздел (лист дерева, без предков: слаг уникален по таблице).
 * Паттерны слагов — только из констант {@see SlugValidator}: STRICT (первый символ — буква) там, где слаг
 * делит сегмент с `<id:\d+>`, ANY — в собственном сегменте (`tag/…`). Гейтятся modman.
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
                'documents'                                                     => 'Documents/document/index',
                'documents/<id:\d+>'                                            => 'Documents/document/view',
                'documents/<slug:' . SlugValidator::SLUG_STRICT . '>/<page:\d+>' => 'Documents/document/category', // <page> — пагинация
                'documents/<slug:' . SlugValidator::SLUG_STRICT . '>'            => 'Documents/document/category',
            ],
        ],
    ],
];
