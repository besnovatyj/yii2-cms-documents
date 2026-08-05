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

<div class="form-area mb-35 scheme-1 primary">
    <form class="form-fields needs-validation" novalidate="novalidate">
        <div class="form-row row">
            <div class="form-col col-12 d-inline-flex">
                <label for="docFilter"></label>
                <input type="text" class="form-control rounded-3 me-1" id="docFilter" placeholder="Поиск...">
                <button class="button shadow rounded-3 primary-50 secondary-75-hover float-end">
                    <span class="button-text white white-hover">Очистить</span>
                </button>
            </div>
        </div>
    </form>
</div>

<?php foreach ($dataProvider->getModels() as $model): ?>
    <?php
    $local_url = Url::to(['/Documents/document/download', 'id' => $model->id]);
    $external_url = $model->external_url;
    if (is_string($external_url) && !empty($external_url)) {
        $link = Html::a(Html::encode($model->title) . '🔗', $external_url, ['class' => '', 'target' => '_blank']);
    } else {
        $link = Html::a(Html::encode($model->title) . '🔗', $local_url, ['class' => '', 'target' => '_blank']);
    }
    ?>
    <div>
        <small><?= Yii::$app->formatter->asDatetime($model->created_at) ?></small>
        <br/>
        <?= $link ?>
        <?php if ($model->type === 'file'): ?>
            <small class="text-muted">
                <?php if ($model->extension): ?>
                    (<?= Html::encode(strtoupper($model->extension)) ?><?php if ($model->file_size): ?>, <?= Yii::$app->formatter->asShortSize($model->file_size, 1) ?><?php endif; ?>)
                <?php elseif ($model->file_size): ?>
                    (<?= Yii::$app->formatter->asShortSize($model->file_size, 1) ?>)
                <?php endif; ?>
            </small>
        <?php endif; ?>
        <br/>
        <small>
            <?= $model->description; ?>
        </small>
        <hr>
    </div>
<?php endforeach; ?>


