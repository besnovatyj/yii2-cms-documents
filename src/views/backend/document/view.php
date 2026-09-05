<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\helpers\DocumentHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\DetailView;

/* @var $this View */
/* @var $document Document */
/* @var $absoluteFrontendUrl string */

$this->title = ($document->title ? $document->title : 'document#' . $document->id);
$this->params['breadcrumbs'][] = ['label' => 'Документы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<p>
    <?= Html::a('Create', ['create'], ['class' => 'btn  btn-success']) ?>
    <?php if ($document->isActive()): ?>
        <?= Html::a('To draft', ['draft', 'id' => $document->id], ['class' => 'btn btn-warning', 'data-method' => 'post']) ?>
    <?php else: ?>
        <?= Html::a('To active', ['activate', 'id' => $document->id], ['class' => 'btn btn-success', 'data-method' => 'post']) ?>
    <?php endif; ?>
    <?= Html::a('Update', ['update', 'id' => $document->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('Delete', ['delete', 'id' => $document->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'confirm' => 'Are you sure?',
            'method' => 'post',
        ],
    ]) ?>
    <a class="btn btn-secondary" target="_blank"
       href="<?= $absoluteFrontendUrl; ?>">
        <i class="bi bi-eye"></i>
    </a>
</p>

<div class="row">
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">Common</div>
            <div class="card-body">
                <?= DetailView::widget([
                    'model' => $document,
                    'attributes' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'external_url',
                        'original_name',
                        'extension',
                        'original_filename',
                        'mime_type',
                        'file_size:shortSize',
                        [
                            'label'=>'Category',
                            'attribute' => 'category_id',
                            'value' => ArrayHelper::getValue($document, 'category.name'),
                            'format' => 'html',
                        ],
                        'uploaded_at:datetime',
                        'document_date:date',
                        'created_at:datetime',
                        'updated_at:datetime',
                        [
                            'attribute' => 'status',
                            'value' => DocumentHelper::statusLabel($document),
                            'format' => 'raw',
                        ],
                    ],
                ]) ?>
            </div>
            <div class="card-footer clearfix"></div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">File</div>
            <div class="card-body">
                <?php
                $local_url = $document->getUploadUrl('original_filename');
                $external_url = $document->external_url;
                if (is_string($local_url) && !empty($local_url)) {
                    $link = Html::a(Html::encode($document->title) . '🔗', $local_url, ['class' => '', 'target' => '_blank']);
                } elseif (is_string($external_url) && !empty($external_url)) {
                    $link = Html::a(Html::encode($document->title) . '🔗', $external_url, ['class' => '', 'target' => '_blank']);
                } else {
                    $link = 'Ссылки нет';
                }
                ?>
                <?= $link ?>
            </div>
            <div class="card-footer clearfix"></div>
        </div>

        <?php $manifest = $document->getManifest(); ?>
        <?php if ($manifest !== null): ?>
            <?php /* Список снят при загрузке архива. Нечитаемые имена означают, что архив
                     упакован без пометки UTF-8: такой архив переупаковывается в ZIP заново. */ ?>
            <div class="card">
                <div class="card-header d-md-flex justify-content-md-between">
                    <div class="pt-1">Archive content</div>
                    <div class="pt-1 text-muted small">
                        файлов: <?= $manifest->total ?>,
                        <?= Yii::$app->formatter->asShortSize($manifest->unpackedSize(), 1) ?> без сжатия
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($manifest->entries === []): ?>
                        <p class="text-muted p-3 mb-0">Архив пуст.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th scope="col">Файл</th>
                                    <th scope="col" class="text-nowrap">Размер</th>
                                    <th scope="col" class="text-nowrap">В архиве</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($manifest->entries as $entry): ?>
                                    <tr>
                                        <td>
                                            <?php if ($entry->encrypted): ?>
                                                <span title="Файл защищён паролем" aria-hidden="true">&#128274;</span>
                                            <?php endif; ?>
                                            <?= Html::encode($entry->path) ?>
                                        </td>
                                        <td class="text-nowrap"><?= Yii::$app->formatter->asShortSize($entry->size, 1) ?></td>
                                        <td class="text-nowrap"><?= Yii::$app->formatter->asShortSize($entry->packedSize, 1) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($manifest->truncated): ?>
                    <div class="card-footer text-muted small">
                        Показаны первые <?= count($manifest->entries) ?> файлов из <?= $manifest->total ?>.
                    </div>
                <?php else: ?>
                    <div class="card-footer clearfix"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


