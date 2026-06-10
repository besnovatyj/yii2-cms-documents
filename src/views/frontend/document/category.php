<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Category;
use yii\data\DataProviderInterface;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider DataProviderInterface */
/* @var $category Category */

$this->title = 'Документы';
$this->params['layoutTitle'] = $this->title;

$this->params['og:title'] = $this->title;
//$this->params['og:image'] = '/static_assets_bd/images/logo.svg';

$this->params['breadcrumbs'][] = ['label' => 'Документы', 'url' => ['index']];

$this->registerMetaTag(['name' => 'keywords', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['keywords']]);
$this->registerMetaTag(['name' => 'description', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['description']]);
$this->registerMetaTag(['name' => 'author', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['name']]);

$this->params['active_category'] = $category; // Для виджета

?>

<section class="shock-section mt-3 mb-5">
    <?= $this->render('_list', [
        'dataProvider' => $dataProvider
    ]) ?>
</section>
