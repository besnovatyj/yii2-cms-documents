<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\widgets\preview\DocumentPreviewWidget;
use yii\base\Module;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $document Document */

$this->title = Html::encode($document->title);

$this->params['og:title'] = $this->title;

$this->params['breadcrumbs'][] = $this->title;

if (Yii::$app->getModule('Config') instanceof Module) {
    $this->registerMetaTag(['name' => 'keywords', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['keywords']]);
    $this->registerMetaTag(['name' => 'description', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['description']]);
    $this->registerMetaTag(['name' => 'author', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['name']]);
}

$external_url = $document->external_url;
if (is_string($external_url) && $external_url !== '') {
    $url = $external_url;
} else {
    $url = Url::to(['/Documents/document/download', 'id' => $document->id]);
}
?>
<section class="container mt-3 mb-5">
    <h1 class="h3 mb-3"><?= Html::encode($document->title) ?></h1>

    <dl class="row text-secondary small mb-3">
        <?php if ($document->document_date): ?>
            <dt class="col-sm-3 col-lg-2 fw-normal">Дата документа</dt>
            <dd class="col-sm-9 col-lg-10"><?= Yii::$app->formatter->asDate($document->document_date) ?></dd>
        <?php endif; ?>

        <?php if ($document->uploaded_at): ?>
            <dt class="col-sm-3 col-lg-2 fw-normal">Загружен</dt>
            <dd class="col-sm-9 col-lg-10"><?= Yii::$app->formatter->asDatetime($document->uploaded_at) ?></dd>
        <?php endif; ?>

        <?php if ($document->hasFile()): ?>
            <dt class="col-sm-3 col-lg-2 fw-normal">Файл</dt>
            <dd class="col-sm-9 col-lg-10">
                <?php if ($document->extension): ?>
                    <span class="text-uppercase"><?= Html::encode($document->extension) ?></span><?= $document->file_size ? ',' : '' ?>
                <?php endif; ?>
                <?php if ($document->file_size): ?>
                    <?= Yii::$app->formatter->asShortSize($document->file_size, 1) ?>
                <?php endif; ?>
            </dd>
        <?php endif; ?>
    </dl>

    <?php if ($document->description): ?>
        <p><?= Html::encode($document->description) ?></p>
    <?php endif; ?>

    <p>
        <a href="<?= Html::encode($url) ?>" class="btn btn-primary" target="_blank" rel="noopener">Скачать</a>
    </p>

    <?php /* Показывает картинку, PDF или список файлов архива; для прочих файлов не выводит ничего. */ ?>
    <?= DocumentPreviewWidget::widget(['document' => $document]) ?>
</section>
