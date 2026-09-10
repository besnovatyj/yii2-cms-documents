<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\readModels;

use Besnovatyj\Contracts\search\SearchDocument;
use Besnovatyj\Documents\entities\Category;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;

class CategoryReadRepository
{
    private TreeQueryScope $treeScope;

    public function __construct()
    {
        $this->treeScope = new TreeQueryScope(Category::class);
    }

    /**
     * Корневая категория дерева — только видимая: корень такая же полноценная категория,
     * как остальные, и снятая с публикации показываться не должна.
     */
    public function getRoot(): ?Category
    {
        return Category::find()->visible()->andWhere(['depth' => 0])->one();
    }

    /**
     * @return Category[]
     */
    public function getAll(): array
    {
        return Category::find()->visible()->orderBy('lft')->all();
    }

    public function find(int $id): ?Category
    {
        return Category::find()->visible()->andWhere(['id' => $id])->one();
    }

    /**
     * Категория по slug для фронтенда — только доступная анонимному посетителю: снятая с
     * публикации (или лежащая в скрытой ветке) не должна открываться по прямой ссылке.
     */
    public function findBySlug(string $slug): ?Category
    {
        return Category::find()->visible()->andWhere(['slug' => $slug])->one();
    }

    /**
     * Категории документов для сквозного поиска — только видимые целиком, вместе с предками
     * ({@see \Besnovatyj\Documents\entities\queries\CategoryQuery::visible()}).
     *
     * @return iterable<SearchDocument>
     */
    public function searchDocuments(): iterable
    {
        $query = Category::find()->visible()->orderBy(['id' => SORT_ASC]);

        /** @var Category $category */
        foreach ($query->each(100) as $category) {
            yield new SearchDocument(
                type: 'documents.category',
                entityId: (int)$category->id,
                route: '/Documents/document/category',
                params: ['slug' => $category->slug],
                title: (string)$category->name,
                text: (string)$category->description,
            );
        }
    }

    public function getTreeWithSubsOf(?Category $category = null): array
    {
        $query = Category::find()->visible()->orderBy(['lft' => SORT_ASC]);
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
