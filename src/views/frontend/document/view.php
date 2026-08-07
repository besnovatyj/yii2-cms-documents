<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Document;
use yii\base\Module;
use yii\helpers\Html;

/** @var $document Document */

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
    $url = \yii\helpers\Url::to(['/Documents/document/download', 'id' => $document->id]);
}
?>
<section class="container mt-3 mb-5">
    <h1 class="h3 mb-3"><?= Html::encode($document->title) ?></h1>

    <p class="text-secondary small mb-3"><?= Yii::$app->formatter->asDatetime($document->created_at) ?></p>

    <?php if ($document->description): ?>
        <p><?= Html::encode($document->description) ?></p>
    <?php endif; ?>

    <a href="<?= Html::encode($url) ?>" class="btn btn-primary" target="_blank" rel="noopener">Скачать</a>
</section>
