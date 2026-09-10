<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\entities\queries;

use Besnovatyj\Documents\entities\Category;
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

    /**
     * Документ доступен анонимному посетителю: опубликован сам И лежит в видимой категории.
     *
     * Одной публикации мало: скрытая категория не должна «протекать» на фронт своими документами —
     * категория проверяется целиком, вместе с предками (см. {@see CategoryQuery::visible()}).
     * Документ без категории (`category_id` NULL) виден: скрывать его не за что.
     *
     * @param string|null $alias алиас таблицы документов, если запрос строится с `alias()`
     */
    public function visible(?string $alias = null): static
    {
        $column = ($alias ? $alias . '.' : '') . 'category_id';

        return $this->active($alias)->andWhere([
            'or',
            [$column => null],
            [$column => Category::find()->visible()->select('id')],
        ]);
    }
}
