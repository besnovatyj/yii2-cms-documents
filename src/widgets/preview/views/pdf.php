<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Встроенный просмотрщик PDF.
 *
 * Без JavaScript остаётся заголовок и подсказка со ссылкой на скачивание —
 * сам файл доступен кнопкой на странице документа в любом случае.
 */

use Besnovatyj\Documents\entities\Document;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $document Document */
/* @var $height string */

$source = Url::to(['/Documents/document/preview', 'id' => $document->id]);
?>

<div class="documents-pdf" data-pdf-viewer data-src="<?= Html::encode($source) ?>"
     style="height: <?= Html::encode($height) ?>">
    <div class="documents-pdf__toolbar">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-pdf-prev disabled
                aria-label="Предыдущая страница">
            <span aria-hidden="true">&larr;</span>
        </button>
        <span class="documents-pdf__status small text-secondary" data-pdf-status role="status">
            Загрузка документа&hellip;
        </span>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-pdf-next disabled
                aria-label="Следующая страница">
            <span aria-hidden="true">&rarr;</span>
        </button>
    </div>
    <div class="documents-pdf__stage" data-pdf-stage>
        <canvas data-pdf-canvas></canvas>
    </div>
</div>
