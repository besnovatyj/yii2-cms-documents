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

<?php if ($dataProvider->getCount() === 0): ?>
    <p class="text-secondary">Документов не найдено.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($dataProvider->getModels() as $model): ?>
            <?php /* Название ведёт на страницу документа: там описание, предпросмотр и скачивание. */ ?>
            <a href="<?= Html::encode(Url::to(['/Documents/document/view', 'id' => $model->id])) ?>"
               class="list-group-item list-group-item-action">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold"><?= Html::encode($model->title) ?></span>
                    <?php if ($model->type === 'file' && $model->extension): ?>
                        <span class="badge text-bg-light border text-uppercase"><?= Html::encode($model->extension) ?></span>
                    <?php endif; ?>
                    <?php if ($model->type === 'file' && $model->file_size): ?>
                        <span class="text-secondary small"><?= Yii::$app->formatter->asShortSize($model->file_size, 1) ?></span>
                    <?php endif; ?>
                    <?php if ($model->type === 'link'): ?>
                        <span class="badge text-bg-light border">ссылка</span>
                    <?php endif; ?>

                    <span class="text-secondary small ms-auto text-nowrap">
                        <?php if ($model->document_date): ?>
                            Документ от <?= Yii::$app->formatter->asDate($model->document_date) ?>
                        <?php elseif ($model->uploaded_at): ?>
                            Загружен <?= Yii::$app->formatter->asDate($model->uploaded_at) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($model->description): ?>
                    <p class="text-secondary small mb-0 mt-1"><?= Html::encode($model->description) ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
