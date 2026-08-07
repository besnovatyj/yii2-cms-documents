<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\entities\Document;
use yii\data\DataProviderInterface;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider DataProviderInterface */
/* @var $model Document */
/* @var $category Category */

?>

<div class="list-group">
    <?php foreach ($dataProvider->getModels() as $model): ?>
        <?php
        $external_url = $model->external_url;
        if (is_string($external_url) && $external_url !== '') {
            $url = $external_url;
        } else {
            $url = Url::to(['/Documents/document/download', 'id' => $model->id]);
        }
        ?>
        <a href="<?= Html::encode($url) ?>" class="list-group-item list-group-item-action" target="_blank" rel="noopener">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-semibold"><?= Html::encode($model->title) ?></span>
                <?php if ($model->type === 'file' && $model->extension): ?>
                    <span class="badge text-bg-light border text-uppercase"><?= Html::encode($model->extension) ?></span>
                <?php endif; ?>
                <?php if ($model->type === 'file' && $model->file_size): ?>
                    <span class="text-secondary small"><?= Yii::$app->formatter->asShortSize($model->file_size, 1) ?></span>
                <?php endif; ?>
                <span class="text-secondary small ms-auto"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></span>
            </div>
            <?php if ($model->description): ?>
                <p class="text-secondary small mb-0 mt-1"><?= Html::encode($model->description) ?></p>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
