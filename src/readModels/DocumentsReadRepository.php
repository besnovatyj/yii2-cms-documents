<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\readModels;


use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\entities\Document;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveQuery;


class DocumentsReadRepository
{
    private TreeQueryScope $treeScope;

    public function __construct()
    {
        $this->treeScope = new TreeQueryScope(Category::class);
    }

    public function count(): int
    {
        return Document::find()->active()->count();
    }

    public function getAllByRange(int $offset, int $limit): array
    {
        return Document::find()
            ->alias('d')
            ->active('d')
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function getAllIterator(): iterable
    {
        return Document::find()->alias('d')->active('d')->each();
    }

    public function getAll(): DataProviderInterface
    {
        $query = Document::find()->alias('d')->active('d');
        return $this->getProvider($query);
    }

    public function getAllByCategory(Category $category): DataProviderInterface
    {
        $query = Document::find()->alias('d')->active('d')->with('category');
        $ids = $this->treeScope->descendantIds($category, andSelf: true);
        $query->andWhere(['d.category_id' => $ids]);
        $query->groupBy('d.id');
        return $this->getProvider($query);
    }

    public function find($id): ?Document
    {
        /** @var $documents Document */
        $documents = Document::find()->active()->andWhere(['id' => $id])->one();
        return $documents;
    }

    private function getProvider(ActiveQuery $query): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => [
                    'id' => [
                        'asc' => ['d.id' => SORT_ASC],
                        'desc' => ['d.id' => SORT_DESC],
                    ],
                    'created_at' => [
                        'asc' => ['d.created_at' => SORT_ASC],
                        'desc' => ['d.created_at' => SORT_DESC],
                    ],
                    'name' => [
                        'asc' => ['d.name' => SORT_ASC],
                        'desc' => ['d.name' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => [
                'pageSizeLimit' => [15, 100],
                'pageSize' => 100
            ]
        ]);
    }
}
