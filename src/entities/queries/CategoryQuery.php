<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\entities\queries;

use Besnovatyj\Documents\entities\Category;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

/* @see \Besnovatyj\Documents\entities\Category */
class CategoryQuery extends ActiveQuery
{
    /**
     * Категория опубликована сама по себе (без учёта дерева).
     *
     * Для фронтенда этого недостаточно: категория внутри скрытого родителя тоже не должна быть
     * доступна — см. {@see visible()}.
     */
    public function active(?string $alias = null): static
    {
        return $this->andWhere([($alias ?? Category::tableName()) . '.status' => Category::STATUS_ACTIVE]);
    }

    /**
     * Категория доступна анонимному посетителю: опубликована сама и не спрятана ни одним из предков.
     *
     * Скрытие родителя обязано скрывать всю ветку — иначе дочерняя категория остаётся открыта по
     * прямой ссылке и попадает в сквозной поиск, хотя из навигации она исчезла. Проверка идёт по
     * ключам Nested Sets одним подзапросом: у видимого узла не должно существовать предка со
     * снятой публикацией.
     *
     * Проверяются предки ЛЮБОГО уровня, включая корневые (`depth = 0`): корень — такой же
     * полноценный раздел с редактируемым статусом, а не служебный контейнер (прежнее исключение
     * корня было наследием старой схемы дерева и оставляло ветку скрытого корня видимой).
     *
     * Реализация повторяет {@see \Besnovatyj\Blog\entities\queries\TaxonomyQuery::visible()} —
     * единая политика видимости деревьев во всех контентных модулях.
     */
    public function visible(?string $alias = null): static
    {
        $table = Category::tableName();
        $self = $alias ?? $table;

        $hiddenAncestor = (new Query())
            ->select(new Expression('1'))
            ->from(['anc' => $table])
            ->where(new Expression(
                "anc.[[tree]] = {$self}.[[tree]] AND anc.[[lft]] < {$self}.[[lft]] AND anc.[[rgt]] > {$self}.[[rgt]]",
            ))
            ->andWhere(['<>', 'anc.status', Category::STATUS_ACTIVE]);

        return $this->active($alias)->andWhere(['not exists', $hiddenAncestor]);
    }
}
