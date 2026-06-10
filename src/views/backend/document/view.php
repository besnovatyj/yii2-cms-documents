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
                        'original_filename',
                        'mime_type',
                        'file_size:shortSize',
                        [
                            'label'=>'Category',
                            'attribute' => 'category_id',
                            'value' => ArrayHelper::getValue($document, 'category.name'),
                            'format' => 'html',
                        ],
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
    </div>
</div>


