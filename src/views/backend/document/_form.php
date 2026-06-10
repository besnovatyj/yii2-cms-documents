<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\forms\backend\DocumentForm;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $model DocumentForm */

$this->registerJs(file_get_contents(__DIR__ . '/_script.js'), $this::POS_END);
?>
<?php $form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data']
]); ?>
<?= $form->errorSummary($model) ?>

<div class="row">
    <!--MAIN-->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-md-flex justify-content-md-between">
                <div class="pt-1">Main</div>
                <a class="btn btn-sm collapse-button" data-bs-toggle="collapse" href="#collapse-main" role="button"
                   aria-expanded="true" aria-controls="collapseMain">
                    <i class="bi bi-plus-lg"></i>
                    <i class="bi bi-dash-lg"></i>
                </a>
            </div>
            <div class="collapse show" id="collapse-main">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'categoryId')->dropDownList(new TreeQueryScope(Category::class)->dropdownTree(), ['class' => 'rounded-0']) ?>
                            <?= $form->field($model, 'status')->dropDownList($model->statusList(), ['class' => 'custom-select']) ?>
                            <?= $form->field($model, 'source')->radioList(DocumentForm::sourceList(), [
                                'item' => function ($index, $label, $name, $checked, $value) {
                                    return Html::tag('div',
                                        Html::radio($name, $checked, ['value' => $value, 'class' => 'form-check-input', 'id' => $name . $value]) .
                                        Html::label($label, $name . $value, ['class' => 'form-check-label']), ['class' => 'form-check']);
                                }]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'description')->textarea(['rows' => '5', 'class' => 'form-control']) ?>
                            <?= $form->field($model, 'file', ['options' => ['id' => 'file-field', 'class' => $model->source !== DocumentForm::SOURCE_FILE ? ' d-none' : '']])->fileInput(['class' => 'rounded-0']) ?>
                            <?= $form->field($model, 'externalUrl', ['options' => ['id' => 'link-field', 'class' => $model->source !== DocumentForm::SOURCE_LINK ? ' d-none' : '']])->textInput(['maxlength' => true, 'class' => 'form-control']) ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2">
                        <?= Html::submitButton('Save', ['class' => 'btn btn-success', 'role' => 'button']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--SEO-->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-md-flex justify-content-md-between">
                <div class="pt-1">SEO</div>
                <a class="btn btn-sm collapse-button" data-bs-toggle="collapse" href="#collapse-SEO" role="button"
                   aria-expanded="false" aria-controls="collapseSEO">
                    <i class="bi bi-plus-lg"></i>
                    <i class="bi bi-dash-lg"></i>
                </a>
            </div>
            <div class="collapse" id="collapse-SEO">
                <div class="card-body">
                    <?= $form->field($model->meta, 'title')->textInput(['class' => 'form-control']) ?>
                    <?= $form->field($model->meta, 'description')->textarea(['rows' => 2, 'class' => 'form-control']) ?>
                    <?= $form->field($model->meta, 'keywords')->textInput(['class' => 'form-control']) ?>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2">
                        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
