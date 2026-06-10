<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\readModels;

use Besnovatyj\Documents\entities\Category;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;

class CategoryReadRepository
{
    private TreeQueryScope $treeScope;

    public function __construct()
    {
        $this->treeScope = new TreeQueryScope(Category::class);
    }

    public function getRoot(): ?Category
    {
        return Category::find()->andWhere(['depth' => 0])->one();
    }

    /**
     * @return Category[]
     */
    public function getAll(): array
    {
        return Category::find()->orderBy('lft')->all();
    }

    public function find(int $id): ?Category
    {
        return Category::find()->andWhere(['id' => $id])->one();
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::find()->andWhere(['slug' => $slug])->one();
    }

    public function getTreeWithSubsOf(Category $category = null): array
    {
        $query = Category::find()->andWhere(['status' => 1])->orderBy(['lft' => SORT_ASC]);
        if ($category) {
            $parents = $this->treeScope->parentsQuery($category)->all();
            if (!empty($parents)) {
                $parent = $parents[count($parents) - 1];
                $query->andWhere(['>=', 'lft', $parent->lft])->andWhere(['<=', 'rgt', $parent->rgt]);
            } else {
                $query->andWhere(['>=', 'lft', $category->lft])->andWhere(['<=', 'rgt', $category->rgt]);
            }
        } else {
            $query->andWhere(['depth' => [0, 1]]);
        }
        return $query->all();
    }
}
