<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Document;
use yii\helpers\Html;

/** @var $document Document */

$this->title = Html::encode($document->title);

$this->params['og:title'] = $this->title;
//$this->params['og:image'] = '/static_assets_bd/images/logo.svg';

$this->params['breadcrumbs'][] = $this->title;

$this->registerMetaTag(['name' => 'keywords', 'content' => Yii::$app->getModule('Config')->params['frontend']['app']['keywords']]);
$this->registerMetaTag(['name' => 'description', 'content' => Yii::$app->getModule('Config')->params['frontend']['app']['description']]);
$this->registerMetaTag(['name' => 'author', 'content' => Yii::$app->getModule('Config')->params['frontend']['app']['name']]);

//$local_url = $document->getUploadedFileUrl('original_filename');
$local_url = \yii\helpers\Url::to(['/Documents/document/download', 'id' => $document->id]);
$external_url = $document->external_url;
if (is_string($local_url) && !empty($local_url)) {
    $link = Html::a(Html::encode($document->title) . '🔗', $local_url, ['class' => '', 'target' => '_blank']);
} elseif (is_string($external_url) && !empty($external_url)) {
    $link = Html::a(Html::encode($document->title) . '🔗', $external_url, ['class' => '', 'target' => '_blank']);
} else {
    $link = 'Ссылки нет';
}
?>
<section class="shock-section mt-3 mb-5">
    <div>
        <small><?= Yii::$app->formatter->asDatetime($document->created_at) ?></small>
        <br/>
        <?= $link ?>
        <br/>
        <small>
            <?= $document->description; ?>
        </small>
        <hr>
    </div>
</section>
