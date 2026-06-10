<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\forms\backend\DocumentForm;
use yii\web\View;

/* @var $this View */
/* @var $document Document */
/* @var $model DocumentForm */

$this->title = 'Update: ' . ($document->title ? $document->title : 'document#' . $document->id);
$this->params['breadcrumbs'][] = ['label' => 'Documents', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => ($document->title ? $document->title : 'document#' . $document->id), 'url' => ['view', 'id' => $document->id]];
$this->params['breadcrumbs'][] = 'Update';

echo $this->render('_form', [
    'model' => $model
]);
