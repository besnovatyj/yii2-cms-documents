<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Documents\widgets\preview;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Ресурсы встроенного просмотрщика PDF.
 *
 * Библиотека pdf.js лежит в самом пакете (media/pdfjs) — сборки нет, файлы
 * подключаются как есть. Публикуется вся директория media: просмотрщик сам
 * находит рядом с собой воркер и шрифты через import.meta.url, поэтому пути
 * из PHP передавать не требуется.
 *
 * Файлы библиотеки распространяются в формате ES-модулей с расширением .mjs;
 * в пакете они лежат под именами .js, чтобы веб-сервер отдавал их как обычные
 * скрипты, — для модуля важен атрибут type, а не расширение файла.
 */
class PdfViewerAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/media';

    public $js = ['pdf-viewer.js'];

    public $css = ['pdf-viewer.css'];

    public $jsOptions = [
        'type' => 'module',
        'position' => View::POS_END,
    ];
}
