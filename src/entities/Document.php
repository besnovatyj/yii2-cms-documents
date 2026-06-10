<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\entities;

use Besnovatyj\Documents\entities\queries\DocumentsQuery;
use Besnovatyj\Meta\Meta;
use Besnovatyj\Meta\MetaBehavior;
use Besnovatyj\Upload\heap\UploadBehavior;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Exception;
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
 * @property string|null $original_filename - Имя файла при загрузке (для ссылок можно не использовать или хранить условно). Включая расширение
 * @property string|null $mime_type - MIME-тип файла (например, application/pdf, image/jpeg)
 * @property integer|null $file_size - Размер файла в байтах (для локальных файлов; для ссылок — можно не заполнять или получать через API)
 * @property integer $category_id - Идентификатор категории
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

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws Exception
     */
    public static function create($title, $description, $type, $external_url, $mime_type, $file_size, $categoryId, $status, Meta $meta, UploadedFile|null $original_filename): self
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
        $document->original_filename = $original_filename;
        $document->meta = $meta;
        return $document;
    }

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    public function edit($title, $description, $status, Meta $meta): void
    {
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->meta = $meta;
        $this->updated_at = new DateTimeImmutable('now', new DateTimeZone(Yii::$app->timeZone))->setTimezone(new DateTimeZone('UTC'))->format('Y.m.d H:i:s');
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
