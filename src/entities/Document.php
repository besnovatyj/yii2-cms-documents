<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\entities;

use Besnovatyj\Documents\entities\queries\DocumentsQuery;
use Besnovatyj\Documents\services\archive\ArchiveManifest;
use Besnovatyj\Meta\Meta;
use Besnovatyj\Meta\MetaBehavior;
use Besnovatyj\Upload\heap\UploadBehavior;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Exception;
use JsonException;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * @property integer $id - Уникальный идентификатор документа
 * @property string $title - Название документа (вводит пользователь)
 * @property string|null $description - Описание документа
 * @property string $type - Тип документа: file или link
 * @property string|null $external_url - Внешняя ссылка (если это ссылка на файлообменник)
 * @property string|null $original_filename - Ключ хранения: санитизированное имя файла на диске (управляется UploadBehavior). Включая расширение
 * @property string|null $original_name - Оригинальное имя файла как его загрузил пользователь (для отдачи при скачивании). Включая расширение
 * @property string|null $extension - Расширение файла без точки (например: pdf, rar, docx)
 * @property string|null $mime_type - MIME-тип файла (например, application/pdf, image/jpeg)
 * @property integer|null $file_size - Размер файла в байтах (для локальных файлов; для ссылок — можно не заполнять или получать через API)
 * @property string|null $manifest_json - JSON-манифест содержимого архива (см. {@see ArchiveManifest})
 * @property integer $category_id - Идентификатор категории
 * @property string|null $uploaded_at - Дата загрузки документа в UTC (автоматически при создании, редактируется вручную)
 * @property string|null $document_date - Дата создания самого документа (дата приказа, письма и т.п.), без времени
 * @property string $created_at - Дата создания записи
 * @property string $updated_at - Дата обновления записи
 * @property integer $status - Статус отображения
 * @property string $meta_json - JSON of meta-obj
 *
 * @property Meta $meta
 * @property Category $category
 *
 * @mixin UploadBehavior
 */
class Document extends ActiveRecord
{
    public Meta $meta;

    public const int STATUS_DRAFT = 0;
    public const int STATUS_ACTIVE = 1;

    /** Расширения файлов, которые показываются картинкой прямо на странице документа. */
    public const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Типы, которые разрешено отдавать браузеру для показа на странице (не на скачивание).
     *
     * Список закрытый и определяется расширением файла, а не колонкой `mime_type`:
     * туда попадает то, что о файле сообщил браузер при загрузке. Показать «внутри»
     * сайта что-то помимо картинок и PDF — например, HTML или SVG — нельзя.
     */
    public const array PREVIEW_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
    ];

    /** Разобранный манифест архива; заполняется при первом обращении. */
    private ?ArchiveManifest $manifest = null;

    /** Манифест уже разбирался (в том числе с результатом null). */
    private bool $manifestLoaded = false;

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws Exception
     */
    public static function create($title, $description, $type, $external_url, $mime_type, $file_size, $categoryId, $status, Meta $meta, UploadedFile|null $original_filename, ?string $original_name = null, ?string $extension = null, ?string $uploadedAt = null, ?string $documentDate = null): self
    {
        $document = new static();
        $document->title = $title;
        $document->description = $description;
        $document->type = $type;
        $document->external_url = $external_url;
        $document->mime_type = $mime_type;
        $document->file_size = $file_size;
        $document->category_id = $categoryId;
        $document->status = $status;
        $document->created_at = new DateTimeImmutable('now', new DateTimeZone(Yii::$app->timeZone))->setTimezone(new DateTimeZone('UTC'))->format('Y.m.d H:i:s');
        // Дата загрузки — отдельная от служебной created_at: её редактируют вручную,
        // например когда документ переносят со старого сайта задним числом.
        $document->uploaded_at = $uploadedAt ?? self::nowUtc();
        $document->document_date = $documentDate;
        // UploadBehavior перетрёт original_filename санитизированным ключом хранения,
        // поэтому читаемое имя и расширение сохраняем в отдельные колонки.
        $document->original_filename = $original_filename;
        $document->original_name = $original_name;
        $document->extension = $extension;
        $document->meta = $meta;
        return $document;
    }

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    public function edit($title, $description, $status, Meta $meta, ?string $uploadedAt = null, ?string $documentDate = null): void
    {
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->meta = $meta;
        $this->uploaded_at = $uploadedAt;
        $this->document_date = $documentDate;
        $this->updated_at = new DateTimeImmutable('now', new DateTimeZone(Yii::$app->timeZone))->setTimezone(new DateTimeZone('UTC'))->format('Y.m.d H:i:s');
    }

    /**
     * Заменяет сведения о загруженном файле — при замене файла на странице редактирования.
     */
    public function changeFile(UploadedFile $file, ?string $mimeType, ?int $fileSize, ?string $originalName, ?string $extension): void
    {
        $this->type = 'file';
        $this->external_url = null;
        $this->original_filename = $file;
        $this->original_name = $originalName;
        $this->extension = $extension;
        $this->mime_type = $mimeType;
        $this->file_size = $fileSize;
        $this->setManifest(null);
    }

    /**
     * Заменяет документ ссылкой на внешний файлообменник, забывая прежний файл.
     */
    public function changeExternalUrl(string $url): void
    {
        $this->type = 'link';
        $this->external_url = $url;
        $this->setManifest(null);
    }

    /**
     * Текущий момент в UTC — в том же формате, в каком хранятся остальные даты записи.
     *
     * @throws \DateInvalidTimeZoneException
     */
    public static function nowUtc(): string
    {
        return new DateTimeImmutable('now', new DateTimeZone(Yii::$app->timeZone))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    public function changeMainCategory($categoryId): void
    {
        $this->category_id = $categoryId;
    }

    public function activate(): void
    {
        if ($this->isActive()) {
            throw new DomainException('Document already active.');
        }
        $this->status = self::STATUS_ACTIVE;
    }

    public function draft(): void
    {
        if ($this->isDraft()) {
            throw new DomainException('Document already draft.');
        }
        $this->status = self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getSeoTitle(): string
    {
        return $this->meta->title ?: $this->title;
    }

    // <editor-fold desc="File">

    /**
     * Документ — локальный файл, а не ссылка на файлообменник.
     */
    public function hasFile(): bool
    {
        return $this->type === 'file' && !empty($this->original_filename);
    }

    /**
     * Расширение файла в нижнем регистре; пустая строка, если оно неизвестно.
     */
    public function normalizedExtension(): string
    {
        return strtolower((string)$this->extension);
    }

    /**
     * Файл показывается картинкой прямо на странице документа.
     */
    public function isImage(): bool
    {
        return $this->hasFile() && in_array($this->normalizedExtension(), self::IMAGE_EXTENSIONS, true);
    }

    /**
     * Файл показывается встроенным просмотрщиком PDF.
     */
    public function isPdf(): bool
    {
        return $this->hasFile() && $this->normalizedExtension() === 'pdf';
    }

    /**
     * MIME-тип для показа файла на странице; null — такой файл показывать нельзя,
     * он доступен только на скачивание.
     */
    public function previewMimeType(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }

        return self::PREVIEW_MIME_TYPES[$this->normalizedExtension()] ?? null;
    }

    /**
     * Файл можно показать прямо на странице документа.
     */
    public function isPreviewable(): bool
    {
        return $this->previewMimeType() !== null;
    }

    /**
     * Имя, под которым файл отдаётся посетителю: как его загрузили,
     * а при отсутствии — название документа с расширением.
     */
    public function downloadName(): string
    {
        return $this->original_name
            ?: $this->title . ($this->extension ? '.' . $this->extension : '');
    }

    // </editor-fold>

    // <editor-fold desc="Archive manifest">

    /**
     * Список файлов внутри архива, снятый при загрузке.
     *
     * null — документ не архив, содержимое не прочиталось либо манифест
     * сохранён устаревшей версией формата.
     */
    public function getManifest(): ?ArchiveManifest
    {
        if (!$this->manifestLoaded) {
            $this->manifest = ArchiveManifest::fromJson($this->manifest_json);
            $this->manifestLoaded = true;
        }

        return $this->manifest;
    }

    /**
     * @throws JsonException
     */
    public function setManifest(?ArchiveManifest $manifest): void
    {
        $this->manifest = $manifest;
        $this->manifestLoaded = true;
        $this->manifest_json = $manifest?->toJson();
    }

    /**
     * {@inheritdoc}
     *
     * Значение колонки могло смениться помимо {@see setManifest()} — например,
     * после refresh() модели, — поэтому разбор сбрасывается.
     */
    public function afterFind(): void
    {
        parent::afterFind();
        $this->manifest = null;
        $this->manifestLoaded = false;
    }

    // </editor-fold>

    // <editor-fold desc="Relations">

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    // </editor-fold>

    public static function tableName(): string
    {
        return '{{%documents_documents}}';
    }

    public function behaviors(): array
    {
        return [
            MetaBehavior::class,
            [
                'class' => UploadBehavior::class,
                'attribute' => 'original_filename',
                'pathTemplate' => 'origin/Documents/{filename}_{pk}.{extension}',
            ],
        ];
    }

    public function transactions(): array
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public static function find(): DocumentsQuery
    {
        return new DocumentsQuery(static::class);
    }
}
