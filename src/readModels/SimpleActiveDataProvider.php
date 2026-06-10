<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\readModels;

use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\QueryInterface;

class SimpleActiveDataProvider extends ActiveDataProvider
{
    public $totalCount;

    /**
     * @throws InvalidConfigException
     */
    protected function prepareModels(): array
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
        }

        return $this->query->all($this->db);
    }

    /**
     * @throws InvalidConfigException
     */
    protected function prepareTotalCount(): int
    {
        if ($this->totalCount === null) {
            throw new InvalidConfigException('The "totalCount" property must be set.');
        }

        return $this->totalCount;
    }
}
