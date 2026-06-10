<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\forms\backend\search\DocumentSearch;
use Besnovatyj\Documents\helpers\DocumentHelper;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;
use Besnovatyj\Backend\Widgets\pagination\LinkPager;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\web\View;

/* @var $this View */
/* @var $searchModel DocumentSearch */
/* @var $dataProvider ActiveDataProvider */

$this->title = 'Documents';
$this->params['breadcrumbs'][] = $this->title;
?>

<p>
    <?= Html::a('Create', ['create'], ['class' => 'btn  btn-success']) ?>
</p>

<div class="card">
    <div class="card-header"><?= $this->title ?></div>
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'layout' => "{summary}\n{items}",
            'columns' => [
                'id',
                'original_filename',
                [
                    'attribute' => 'external_url',
                    'value' => function (Document $model) {
                        $title = StringHelper::truncate($model->external_url, 30);
                        return Html::a($title, $model->external_url, ['target' => '_blank']);
                    },
                    'format' => 'raw',
                ],
                [
                    'attribute' => 'title',
                    'value' => function (Document $model) {
                        return !empty($model->title) ? Html::a(Html::encode($model->title), ['view', 'id' => $model->id]) : Html::a(Html::encode('Empty title'), ['view', 'id' => $model->id]);
                    },
                    'format' => 'raw',
                    'contentOptions' => ['data-label' => 'Title or desc'],
                ],
                [
                    'attribute' => 'category_id',
                    'filter' => new TreeQueryScope(Category::class)->dropdownTree(),
                    'value' => 'category.name',
                    'format' => 'html',
                    'contentOptions' => ['data-label' => 'Taxonomy'],
                ],
                [
                    'attribute' => 'status',
                    'filter' => $searchModel->statusList(),
                    'value' => function (Document $model) {
                        return DocumentHelper::statusLabel($model);
                    },
                    'format' => 'raw',
                    'contentOptions' => ['style' => 'min-height: 40px', 'data-label' => 'Status'],
                ],
            ],
        ]); ?>
    </div>
    <div class="card-footer">
        <?= LinkPager::widget([
            'pagination' => $dataProvider->getPagination(),
        ]) ?>
    </div>
</div>
