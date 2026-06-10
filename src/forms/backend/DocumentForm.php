<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\forms\backend;

use Besnovatyj\Forms\CompositeForm;
use Besnovatyj\Meta\MetaForm;
use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\entities\Document;
use yii\web\UploadedFile;

/**
 * @property MetaForm $meta
 */
class DocumentForm extends CompositeForm
{
    const string SOURCE_FILE = 'file';
    const string SOURCE_LINK = 'link';

    public string $source = self::SOURCE_FILE; // по умолчанию — файл

    public string $title = '';
    public string|null $description = null;
    public string|null $externalUrl = null;
    public UploadedFile|string|null $file = null;
    public int|null $categoryId = null;
    public int|null $status = null;

    /**
     * TODO 🔁 Логика на бэкенде (общая)
     *
     * При создании документа:
     * Если выбран файл → сохраняешь его на сервер, записываешь путь в storage_path, оставляешь external_url = NULL.
     * Если введена ссылка → проверяешь валидность URL, записываешь в external_url, оставляешь storage_path = NULL.
     *
     * При редактировании:
     * Можно заменить файл → удаляешь старый, загружаешь новый.
     * Можно заменить ссылку → просто обновляешь external_url.
     *
     * При удалении:
     * Если это локальный файл → удаляешь его с сервера.
     * Если это ссылка → просто удаляешь запись из БД.
     */

    public function __construct(?Document $document = null, $config = [])
    {
        if ($document) {
            $this->title = $document->title;
            $this->description = $document->description;
            $this->externalUrl = $document->external_url;
            $this->categoryId = $document->category_id;
            $this->status = $document->status;
            $this->meta = new MetaForm($document->meta);
        } else {
            $this->meta = new MetaForm();
        }
        parent::__construct($config);
    }

    public function beforeValidate(): bool
    {
        if (parent::beforeValidate()) {
            $this->file = UploadedFile::getInstance($this, 'file');
            return true;
        }
        return false;
    }

    public function rules(): array
    {
        return [
            [['title', 'status', 'categoryId',], 'required'],
            [['title', 'externalUrl'], 'string', 'max' => 255],
            ['description', 'string'],
            [['categoryId',], 'integer'],
            ['status', 'in', 'range' => [Document::STATUS_DRAFT, Document::STATUS_ACTIVE]],
            ['file', 'file', 'maxSize' => 1024 * 1024 * 1024 * 50],
            [['file', 'externalUrl'], 'validateSource'], // Кастомная валидация

            [['source'], 'in', 'range' => [self::SOURCE_FILE, self::SOURCE_LINK]],
        ];
    }

    public function validateSource($attribute, $params): void
    {
        if ($this->file && $this->externalUrl) {
            $this->addError('file', 'Нельзя одновременно загружать файл и указывать внешнюю ссылку.');
            $this->addError('externalUrl', 'Нельзя одновременно загружать файл и указывать внешнюю ссылку.');
        } elseif (!$this->file && !$this->externalUrl) {
            $this->addError('file', 'Необходимо либо загрузить файл, либо указать внешнюю ссылку.');
            $this->addError('externalUrl', 'Необходимо либо загрузить файл, либо указать внешнюю ссылку.');
        }

        if ($this->file) {
            // Yii сам проверит UploadedFile, но можно добавить mime-валидацию
            $allowedTypes = ['pdf', 'docx', 'xlsx', 'jpg', 'png','zip'];
            $ext = strtolower(pathinfo($this->file->name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes)) {
                $this->addError('file', 'Недопустимый тип файла.');
            }
        }

        if ($this->externalUrl && !filter_var($this->externalUrl, FILTER_VALIDATE_URL)) {
            $this->addError('externalUrl', 'Некорректный URL.');
        }
    }

    protected function internalForms(): array
    {
        return ['meta'];
    }

    public static function sourceList(): array
    {
        return [
            DocumentForm::SOURCE_FILE => 'Загрузить файл с компьютера',
            DocumentForm::SOURCE_LINK => 'Вставить ссылку на документ',
        ];
    }

    public function statusList(): array
    {
        return [
            Document::STATUS_ACTIVE => 'ACTIVE',
            Document::STATUS_DRAFT => 'DRAFT',
        ];
    }

}
