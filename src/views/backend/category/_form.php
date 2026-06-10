<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\forms\backend\CategoryForm;
use yii\bootstrap5\ActiveForm;

/**
 * @var $model CategoryForm
 */

?>
<?php $form = ActiveForm::begin(); ?>
<?= $form->errorSummary($model) ?>
<?php
if ($model->parentId !== null) {
    echo $form->field($model, 'parentId')->hiddenInput()->label(false);
}
?>
<?= $form->field($model, 'nodeId')->hiddenInput()->label(false) ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'description')->textarea(['rows' => 2]) ?>
<?= $form->field($model, 'status')->dropDownList([true => 'Вкл.', false => 'Выкл.']) ?>

<div class="card">
    <div class="card-header d-md-flex justify-content-md-between">
        <div class="pt-1">Meta</div>
        <a class="btn btn-sm collapse-button" data-bs-toggle="collapse" href="#documents-category" role="button"
           aria-expanded="false" aria-controls="collapseExample">
            <i class="bi bi-plus-lg"></i>
            <i class="bi bi-dash-lg"></i>
        </a>
    </div>
    <div class="collapse" id="documents-category">
        <div class="card-body">

            <?= $form->field($model->meta, 'title')->textInput() ?>
            <?= $form->field($model->meta, 'description')->textarea(['rows' => 2]) ?>
            <?= $form->field($model->meta, 'keywords')->textInput() ?>

        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
