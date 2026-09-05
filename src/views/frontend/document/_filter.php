<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Фильтр и сортировка списка документов.
 *
 * Обычная форма GET на нативных полях браузера: календарь и выпадающие списки
 * рисует сам браузер, поэтому фильтр одинаково работает на телефоне и без
 * JavaScript, а подобранный список остаётся в адресе страницы.
 */

use Besnovatyj\Documents\forms\frontend\DocumentFilterForm;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $filter DocumentFilterForm */
/* @var $action array Маршрут списка: подставляется в action формы, чтобы отбор начинался с первой страницы */
?>

<?= Html::beginForm($action, 'get', ['class' => 'row g-2 align-items-end mb-4']) ?>
    <div class="col-12 col-md-4">
        <?= Html::label($filter->getAttributeLabel('q'), 'documents-filter-q', ['class' => 'form-label small text-secondary']) ?>
        <?= Html::textInput('q', $filter->q, [
            'id' => 'documents-filter-q',
            'class' => 'form-control',
            'placeholder' => 'Часть названия документа',
        ]) ?>
    </div>

    <div class="col-6 col-md-2">
        <?= Html::label($filter->getAttributeLabel('dateField'), 'documents-filter-field', ['class' => 'form-label small text-secondary']) ?>
        <?= Html::dropDownList('dateField', $filter->dateField, DocumentFilterForm::dateFieldList(), [
            'id' => 'documents-filter-field',
            'class' => 'form-select',
        ]) ?>
    </div>

    <div class="col-6 col-md-2">
        <?= Html::label($filter->getAttributeLabel('dateFrom'), 'documents-filter-from', ['class' => 'form-label small text-secondary']) ?>
        <?= Html::input('date', 'dateFrom', $filter->dateFrom, [
            'id' => 'documents-filter-from',
            'class' => 'form-control',
        ]) ?>
    </div>

    <div class="col-6 col-md-2">
        <?= Html::label($filter->getAttributeLabel('dateTo'), 'documents-filter-to', ['class' => 'form-label small text-secondary']) ?>
        <?= Html::input('date', 'dateTo', $filter->dateTo, [
            'id' => 'documents-filter-to',
            'class' => 'form-control',
        ]) ?>
    </div>

    <div class="col-6 col-md-2">
        <?= Html::label($filter->getAttributeLabel('sort'), 'documents-filter-sort', ['class' => 'form-label small text-secondary']) ?>
        <?= Html::dropDownList('sort', $filter->sort, DocumentFilterForm::sortList(), [
            'id' => 'documents-filter-sort',
            'class' => 'form-select',
        ]) ?>
    </div>

    <div class="col-12 d-flex gap-2">
        <?= Html::submitButton('Показать', ['class' => 'btn btn-primary']) ?>
        <?php if ($filter->isApplied()): ?>
            <?= Html::a('Сбросить', $action, ['class' => 'btn btn-outline-secondary']) ?>
        <?php endif; ?>
    </div>
<?= Html::endForm() ?>
