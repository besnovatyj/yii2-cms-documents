<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Document;
use yii\helpers\Html;

/** @var $document Document */

$this->title = Html::encode($document->title);

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
<section class="container">
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
