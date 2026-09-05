<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Показ документа-картинки.
 *
 * Файл берётся прямо с домена статики: он и так лежит там открыто, а отдавать
 * картинку через PHP значило бы нагружать приложение без всякой пользы.
 */

use Besnovatyj\Documents\entities\Document;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $document Document */

$url = $document->getUploadUrl('original_filename');
?>

<?php if ($url): ?>
    <figure class="mb-0">
        <a href="<?= Html::encode($url) ?>" target="_blank" rel="noopener">
            <?= Html::img($url, [
                'alt' => $document->title,
                'class' => 'img-fluid rounded border',
                'loading' => 'lazy',
            ]) ?>
        </a>
        <figcaption class="text-secondary small mt-2">
            Нажмите на изображение, чтобы открыть его в полном размере.
        </figcaption>
    </figure>
<?php endif; ?>
