<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\services\manage;

use Besnovatyj\Meta\Meta;
use DomainException;
use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\forms\backend\DocumentForm;
use Besnovatyj\Documents\repositories\CategoryRepository;
use Besnovatyj\Documents\repositories\DocumentRepository;
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

    public function __construct(
        DocumentRepository $documents,
        CategoryRepository $categories
    )
    {
        $this->documents = $documents;
        $this->categories = $categories;
    }

    /**
     * @throws Exception
     */
    public function create(DocumentForm $form): Document
    {
        $category = $this->categories->get($form->categoryId);

        $mimeType = null;
        $fileSize = null;

        if ($form->file instanceof UploadedFile) {
            $type = 'file';
            $mimeType = $form->file->type;
            $fileSize = $form->file->size;
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
        );
        $this->documents->save($document);
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
        );
        $document->changeMainCategory($category->id);
        $this->documents->save($document);
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
}
