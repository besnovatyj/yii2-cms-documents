<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\forms\backend;

use Besnovatyj\Forms\CompositeForm;
use Besnovatyj\Meta\MetaForm;
use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\entities\Document;
use DateTimeImmutable;
use DateTimeZone;
use Yii;
use yii\web\UploadedFile;

/**
 * @property MetaForm $meta
 */
class DocumentForm extends CompositeForm
{
    const string SOURCE_FILE = 'file';
    const string SOURCE_LINK = 'link';

    public string $source = self::SOURCE_FILE; // по умолчанию — файл

    /** Формат значения поля «дата загрузки» — как его присылает input[type=datetime-local]. */
    public const string UPLOADED_AT_FORMAT = 'Y-m-d H:i';

    /** Формат значения поля «дата документа» — как его присылает input[type=date]. */
    public const string DOCUMENT_DATE_FORMAT = 'Y-m-d';

    public string $title = '';
    public string|null $description = null;
    public string|null $externalUrl = null;
    public UploadedFile|string|null $file = null;
    public int|null $categoryId = null;
    public int|null $status = null;

    /** Дата загрузки документа в часовом поясе приложения; в БД уходит в UTC. */
    public string|null $uploadedAt = null;

    /** Дата создания самого документа; хранится и вводится как есть, без часовых поясов. */
    public string|null $documentDate = null;

    /** Редактирование существующего документа: файл и ссылку можно оставить прежними. */
    private bool $isNewDocument = true;

    /**
     * Сведения об уже загруженном файле — только для показа рядом с полем выбора.
     *
     * Держим тремя скалярами, а не самим документом: форма показывает, что файл
     * есть, и не получает возможности его менять в обход сервиса.
     */
    private ?string $currentFileName = null;
    private ?int $currentFileSize = null;
    private ?string $currentFileUrl = null;

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

    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(?Document $document = null, $config = [])
    {
        if ($document) {
            $this->isNewDocument = false;
            $this->title = $document->title;
            $this->description = $document->description;
            $this->externalUrl = $document->external_url;
            $this->categoryId = $document->category_id;
            $this->status = $document->status;
            $this->source = $document->type === 'link' ? self::SOURCE_LINK : self::SOURCE_FILE;
            $this->uploadedAt = self::utcToLocal($document->uploaded_at);
            $this->documentDate = $document->document_date;
            if ($document->hasFile()) {
                $this->currentFileName = $document->downloadName();
                $this->currentFileSize = $document->file_size;
                $this->currentFileUrl = $document->getUploadUrl('original_filename');
            }
            $this->meta = new MetaForm($document->meta);
        } else {
            // Дату загрузки нового документа проставляем сразу — её видно в форме и можно поправить.
            $this->uploadedAt = self::utcToLocal(Document::nowUtc());
            $this->meta = new MetaForm();
        }
        parent::__construct($config);
    }

    public function beforeValidate(): bool
    {
        if (parent::beforeValidate()) {
            $this->file = UploadedFile::getInstance($this, 'file');
            // input[type=datetime-local] присылает дату через «T» и иногда с секундами.
            if (is_string($this->uploadedAt) && $this->uploadedAt !== '') {
                $this->uploadedAt = substr(str_replace('T', ' ', trim($this->uploadedAt)), 0, 16);
            }
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

            [['uploadedAt', 'documentDate'], 'default', 'value' => null],
            ['uploadedAt', 'date', 'format' => 'php:' . self::UPLOADED_AT_FORMAT],
            ['documentDate', 'date', 'format' => 'php:' . self::DOCUMENT_DATE_FORMAT],

            [['source'], 'in', 'range' => [self::SOURCE_FILE, self::SOURCE_LINK]],
        ];
    }

    public function validateSource($attribute, $params): void
    {
        if ($this->file && $this->externalUrl) {
            $this->addError('file', 'Нельзя одновременно загружать файл и указывать внешнюю ссылку.');
            $this->addError('externalUrl', 'Нельзя одновременно загружать файл и указывать внешнюю ссылку.');
        } elseif (!$this->file && !$this->externalUrl && $this->isNewDocument) {
            // При редактировании пустые поля означают «оставить прежний файл или ссылку»,
            // иначе нельзя было бы поправить одно только название или дату.
            $this->addError('file', 'Необходимо либо загрузить файл, либо указать внешнюю ссылку.');
            $this->addError('externalUrl', 'Необходимо либо загрузить файл, либо указать внешнюю ссылку.');
        }

//        if ($this->file) {
//            // Yii сам проверит UploadedFile, но можно добавить mime-валидацию
//            $allowedTypes = ['pdf', 'docx', 'xlsx', 'jpg', 'png','zip'];
//            $ext = strtolower(pathinfo($this->file->name, PATHINFO_EXTENSION));
//            if (!in_array($ext, $allowedTypes)) {
//                $this->addError('file', 'Недопустимый тип файла.');
//            }
//        }

        if ($this->externalUrl && !filter_var($this->externalUrl, FILTER_VALIDATE_URL)) {
            $this->addError('externalUrl', 'Некорректный URL.');
        }
    }

    /**
     * У документа уже есть загруженный файл.
     *
     * Поле выбора файла пустует и при заполненном документе, поэтому рядом с ним
     * форма показывает, что именно загружено сейчас.
     */
    public function hasCurrentFile(): bool
    {
        return $this->currentFileName !== null;
    }

    /**
     * Имя уже загруженного файла — то же, под которым он отдаётся на скачивание.
     */
    public function currentFileName(): ?string
    {
        return $this->currentFileName;
    }

    /**
     * Размер уже загруженного файла в байтах; null, если он неизвестен.
     */
    public function currentFileSize(): ?int
    {
        return $this->currentFileSize;
    }

    /**
     * Адрес уже загруженного файла на домене статики — по нему файл можно открыть и проверить.
     */
    public function currentFileUrl(): ?string
    {
        return $this->currentFileUrl;
    }

    /**
     * Дата загрузки в том виде, в каком она хранится в БД: UTC либо null.
     *
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function uploadedAtUtc(): ?string
    {
        if ($this->uploadedAt === null || trim($this->uploadedAt) === '') {
            return null;
        }

        return new DateTimeImmutable($this->uploadedAt, new DateTimeZone(Yii::$app->timeZone))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * Дата самого документа для записи в БД: без времени и часового пояса.
     */
    public function documentDateValue(): ?string
    {
        if ($this->documentDate === null || trim($this->documentDate) === '') {
            return null;
        }

        return $this->documentDate;
    }

    /**
     * Переводит хранимую дату UTC в часовой пояс приложения — в нём её видит и правит редактор.
     *
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    private static function utcToLocal(?string $utc): ?string
    {
        if ($utc === null || trim($utc) === '') {
            return null;
        }

        return new DateTimeImmutable($utc, new DateTimeZone('UTC'))
            ->setTimezone(new DateTimeZone(Yii::$app->timeZone))
            ->format(self::UPLOADED_AT_FORMAT);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'uploadedAt' => 'Upload date',
            'documentDate' => 'Document date',
        ]);
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
