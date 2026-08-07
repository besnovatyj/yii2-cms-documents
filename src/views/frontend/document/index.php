<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\entities\Document;
use yii\base\Module;
use yii\data\DataProviderInterface;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider DataProviderInterface */
/* @var $model Document */
/* @var $category Category */

$this->title = 'Документы';

$this->params['og:title'] = $this->title;

$this->params['breadcrumbs'][] = $this->title;

if (Yii::$app->getModule('Config') instanceof Module) {
    $this->registerMetaTag(['name' => 'keywords', 'content' => Yii::$app->getModule('Config')->params['frontend']['app']['keywords']]);
    $this->registerMetaTag(['name' => 'description', 'content' => Yii::$app->getModule('Config')->params['frontend']['app']['description']]);
    $this->registerMetaTag(['name' => 'author', 'content' => Yii::$app->getModule('Config')->params['frontend']['app']['name']]);
}

?>

<section class="container mt-3 mb-5">
    <?= $this->render('_list', [
        'dataProvider' => $dataProvider
    ]) ?>
</section>
