<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Список файлов внутри архива.
 *
 * Список снят при загрузке документа и хранится вместе с ним, поэтому показ
 * страницы архив не открывает. Каждый файл можно скачать по отдельности —
 * кроме защищённых паролем: их содержимое без пароля не извлечь.
 */

use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\services\archive\ArchiveManifest;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $document Document */
/* @var $manifest ArchiveManifest */
?>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold">Содержимое архива</span>
        <span class="text-secondary small">
            Файлов: <?= $manifest->total ?>,
            <?= Yii::$app->formatter->asShortSize($manifest->unpackedSize(), 1) ?> в распакованном виде
        </span>
    </div>

    <?php if ($manifest->entries === []): ?>
        <div class="card-body text-secondary">Архив пуст.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                <tr>
                    <th scope="col">Файл</th>
                    <th scope="col" class="text-nowrap">Размер</th>
                    <th scope="col" class="text-nowrap d-none d-md-table-cell">Изменён</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($manifest->entries as $entry): ?>
                    <tr>
                        <td>
                            <?php if ($entry->encrypted): ?>
                                <?php /* Символом, а не иконочным шрифтом: виджет не должен зависеть от набора иконок темы. */ ?>
                                <span class="text-secondary" title="Файл защищён паролем">
                                    <span aria-hidden="true">&#128274;</span>
                                    <span class="visually-hidden">Защищён паролем:</span>
                                    <?= Html::encode($entry->path) ?>
                                </span>
                            <?php else: ?>
                                <?= Html::a(
                                    Html::encode($entry->path),
                                    Url::to(['/Documents/document/archive-file', 'id' => $document->id, 'index' => $entry->index]),
                                    ['class' => 'text-decoration-none']
                                ) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap text-secondary small">
                            <?= Yii::$app->formatter->asShortSize($entry->size, 1) ?>
                        </td>
                        <td class="text-nowrap text-secondary small d-none d-md-table-cell">
                            <?= $entry->modifiedAt !== null
                                ? Yii::$app->formatter->asDate($entry->modifiedAt)
                                : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($manifest->truncated): ?>
        <div class="card-footer text-secondary small">
            Показаны первые <?= count($manifest->entries) ?> файлов из <?= $manifest->total ?>.
            Остальные доступны после скачивания архива.
        </div>
    <?php endif; ?>
</div>
