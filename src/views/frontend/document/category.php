<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\forms\frontend\DocumentFilterForm;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;
use yii\base\Module;
use yii\bootstrap5\LinkPager;
use yii\data\DataProviderInterface;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider DataProviderInterface */
/* @var $filter DocumentFilterForm */
/* @var $category Category */

$this->title = 'Документы';

$this->params['og:title'] = $this->title;

$this->params['breadcrumbs'] = new TreeQueryScope(Category::class)->breadcrumbs($category, urlCallback: function ($item) use ($category) {
    if ($item->id !== $category->id) {
        return Url::to(['category', 'slug' => $item->slug]);
    }
    return false;
});

if (Yii::$app->getModule('Config') instanceof Module) {
    $this->registerMetaTag(['name' => 'keywords', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['keywords']]);
    $this->registerMetaTag(['name' => 'description', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['description']]);
    $this->registerMetaTag(['name' => 'author', 'content' => \Yii::$app->getModule('Config')->params['frontend']['app']['name']]);
}

?>

<section class="container mt-3 mb-5">
    <?= $this->render('_filter', [
        'filter' => $filter,
        'action' => ['category', 'slug' => $category->slug],
    ]) ?>

    <?= $this->render('_list', [
        'dataProvider' => $dataProvider
    ]) ?>

    <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
</section>
