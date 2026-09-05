<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Documents\widgets\preview;

use Besnovatyj\Documents\entities\Document;
use yii\base\InvalidConfigException;
use yii\base\Widget;

/**
 * Показ содержимого документа прямо на его странице.
 *
 * Один виджет на все поддерживаемые виды файлов — что именно показать,
 * решается по расширению загруженного файла:
 *
 * - картинка (jpg, jpeg, png, gif) — тегом img с домена статики;
 * - PDF — встроенным просмотрщиком на pdf.js, постранично;
 * - ZIP — списком файлов внутри архива с возможностью скачать любой из них.
 *
 * Для всего остального виджет не выводит ничего: документ остаётся доступен
 * кнопкой скачивания. Ссылки на файлообменниках не показываются никогда —
 * что лежит по внешнему адресу, модуль не знает.
 *
 * ```php
 * <?= DocumentPreviewWidget::widget(['document' => $document]) ?>
 * ```
 */
class DocumentPreviewWidget extends Widget
{
    /** Документ, содержимое которого показывается. */
    public Document $document;

    /**
     * Высота области просмотра PDF — CSS-значение.
     *
     * Просмотрщик занимает её целиком и прокручивается внутри, чтобы длинный
     * документ не растягивал страницу.
     */
    public string $pdfHeight = '80vh';

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->document)) {
            throw new InvalidConfigException('DocumentPreviewWidget: свойство "document" должно быть задано.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        if ($this->document->isImage()) {
            return $this->render('image', ['document' => $this->document]);
        }

        if ($this->document->isPdf()) {
            PdfViewerAsset::register($this->view);

            return $this->render('pdf', [
                'document' => $this->document,
                'height' => $this->pdfHeight,
            ]);
        }

        if ($manifest = $this->document->getManifest()) {
            return $this->render('archive', [
                'document' => $this->document,
                'manifest' => $manifest,
            ]);
        }

        return '';
    }
}
