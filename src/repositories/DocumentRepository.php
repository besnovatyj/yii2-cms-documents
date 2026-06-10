<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\repositories;

use Besnovatyj\Documents\entities\Document;
use RuntimeException;
use Throwable;
use yii\db\Exception;
use yii\db\StaleObjectException;

class DocumentRepository
{

    public function get($id): Document
    {
        if (!$document = Document::findOne($id)) {
            throw new NotFoundException('Documents is not found.');
        }
        return $document;
    }

    public function existsByMainCategory($id): bool
    {
        return Document::find()->andWhere(['category_id' => $id])->exists();
    }

    /**
     * @throws Exception
     */
    public function save(Document $document): void
    {
        if (!$document->save()) {
            throw new RuntimeException('Saving error.');
        }
    }

    /**
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function remove(Document $document): void
    {
        if (!$document->delete()) {
            throw new RuntimeException('Removing error.');
        }
    }
}
