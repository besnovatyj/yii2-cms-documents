<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\forms\backend\DocumentForm;
use yii\web\View;

/* @var $this View */
/* @var $model DocumentForm */

$this->title = 'Create';
$this->params['breadcrumbs'][] = ['label' => 'Documents', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

echo $this->render('_form', ['model' => $model]);
