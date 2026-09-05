<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\readModels;


use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\forms\frontend\DocumentFilterForm;
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

    public function getAll(?DocumentFilterForm $filter = null): DataProviderInterface
    {
        $query = Document::find()->alias('d')->active('d');
        return $this->getProvider($query, $filter);
    }

    public function getAllByCategory(Category $category, ?DocumentFilterForm $filter = null): DataProviderInterface
    {
        $query = Document::find()->alias('d')->active('d')->with('category');
        $ids = $this->treeScope->descendantIds($category, andSelf: true);
        $query->andWhere(['d.category_id' => $ids]);
        $query->groupBy('d.id');
        return $this->getProvider($query, $filter);
    }

    public function find($id): ?Document
    {
        /** @var $documents Document */
        $documents = Document::find()->active()->andWhere(['id' => $id])->one();
        return $documents;
    }

    /**
     * Собирает провайдер списка с учётом фильтра и выбранной сортировки.
     *
     * Порядок задаётся запросу напрямую, а не через компонент Sort: варианты
     * сортировки посетитель выбирает одним списком в форме фильтра, и все они
     * перечислены в {@see DocumentFilterForm::sortMap()}.
     */
    private function getProvider(ActiveQuery $query, ?DocumentFilterForm $filter = null): ActiveDataProvider
    {
        $filter ??= new DocumentFilterForm();
        $this->applyFilter($query, $filter);

        return new ActiveDataProvider([
            'query' => $query->orderBy($filter->orderBy()),
            'sort' => false,
            'pagination' => [
                'pageSizeLimit' => [15, 100],
                'pageSize' => 100
            ]
        ]);
    }

    /**
     * Накладывает условия фильтра на запрос списка.
     */
    private function applyFilter(ActiveQuery $query, DocumentFilterForm $filter): void
    {
        if ($text = $filter->searchText()) {
            $query->andWhere(['like', 'd.title', $text]);
        }

        $column = $filter->filtersByDocumentDate() ? 'd.document_date' : 'd.uploaded_at';

        if ($from = $filter->dateFromValue()) {
            $query->andWhere(['>=', $column, $from]);
        }
        if ($to = $filter->dateToValue()) {
            $query->andWhere(['<=', $column, $to]);
        }
    }
}
