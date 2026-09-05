<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\services\manage;

use Besnovatyj\Documents\services\archive\ZipReader;
use Besnovatyj\Meta\Meta;
use DomainException;
use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\forms\backend\DocumentForm;
use Besnovatyj\Documents\repositories\CategoryRepository;
use Besnovatyj\Documents\repositories\DocumentRepository;
use JsonException;
use Random\RandomException;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\db\Exception;
use yii\db\StaleObjectException;
use yii\web\UploadedFile;

class DocumentsManageService
{
    private DocumentRepository $documents;
    private CategoryRepository $categories;
    private ZipReader $archives;

    public function __construct(
        DocumentRepository $documents,
        CategoryRepository $categories,
        ZipReader $archives
    )
    {
        $this->documents = $documents;
        $this->categories = $categories;
        $this->archives = $archives;
    }

    /**
     * @throws Exception
     */
    public function create(DocumentForm $form): Document
    {
        $category = $this->categories->get($form->categoryId);

        $mimeType = null;
        $fileSize = null;
        $originalName = null;
        $extension = null;

        if ($form->file instanceof UploadedFile) {
            $type = 'file';
            $mimeType = $form->file->type;
            $fileSize = $form->file->size;
            $originalName = $form->file->name;
            $extension = $form->file->getExtension() ?: null;
        } else {
            $type = 'link';
        }

        $document = Document::create(
            $form->title,
            $form->description,
            $type,
            $form->externalUrl,
            $mimeType,
            $fileSize,
            $category->id,
            $form->status,
            new Meta(
                $form->meta->title,
                $form->meta->description,
                $form->meta->keywords
            ),
            $form->file,
            $originalName,
            $extension,
            $form->uploadedAtUtc(),
            $form->documentDateValue(),
        );
        $this->documents->save($document);
        $this->refreshManifest($document);
        return $document;
    }

    /**
     * @throws Exception
     */
    public function edit($id, DocumentForm $form): void
    {
        $document = $this->documents->get($id);
        $category = $this->categories->get($form->categoryId);
        $document->edit(
            $form->title,
            $form->description,
            $form->status,
            new Meta(
                $form->meta->title,
                $form->meta->description,
                $form->meta->keywords
            ),
            $form->uploadedAtUtc(),
            $form->documentDateValue(),
        );
        $document->changeMainCategory($category->id);

        // Пустые поля источника означают «оставить прежний файл или ссылку»:
        // трогаем их, только если редактор действительно прислал новое значение.
        $fileReplaced = false;
        if ($form->file instanceof UploadedFile) {
            $document->changeFile(
                $form->file,
                $form->file->type,
                $form->file->size,
                $form->file->name,
                $form->file->getExtension() ?: null,
            );
            $fileReplaced = true;
        } elseif ($form->externalUrl && $form->externalUrl !== $document->external_url) {
            $document->changeExternalUrl($form->externalUrl);
        }

        $this->documents->save($document);

        if ($fileReplaced) {
            $this->refreshManifest($document);
        }
    }

    /**
     * @throws Exception
     */
    public function activate($id): void
    {
        $document = $this->documents->get($id);
        $document->activate();
        $this->documents->save($document);
    }

    /**
     * @throws Exception
     */
    public function draft($id): void
    {
        $document = $this->documents->get($id);
        $document->draft();
        $this->documents->save($document);
    }

    /**
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function remove($id): void
    {
        $document = $this->documents->get($id);
        $this->documents->remove($document);
    }

    /**
     * Снимает список файлов архива и сохраняет его в документе.
     *
     * Вызывается только после записи документа: до неё UploadBehavior ещё не
     * положил файл на диск. Нечитаемый архив — не повод отклонять загрузку:
     * документ просто останется без списка содержимого.
     *
     * @throws JsonException
     */
    private function refreshManifest(Document $document): void
    {
        if (!$document->hasFile() || !$this->archives->supports($document->extension)) {
            return;
        }

        $path = $document->getUploadPath('original_filename');
        if ($path === null) {
            return;
        }

        $document->setManifest($this->archives->readManifest($path));
        $this->documents->updateManifest($document);
    }
}
