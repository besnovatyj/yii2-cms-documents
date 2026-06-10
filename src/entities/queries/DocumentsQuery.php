<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\entities\queries;

use Besnovatyj\Documents\entities\Document;
use yii\db\ActiveQuery;

class DocumentsQuery extends ActiveQuery
{
    /**
     * @param null $alias
     * @return $this
     */
    public function active($alias = null): static
    {
        return $this->andWhere([
            ($alias ? $alias . '.' : '') . 'status' => Document::STATUS_ACTIVE,
        ]);
    }
}
