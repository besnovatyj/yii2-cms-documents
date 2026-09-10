<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\readModels;


use Besnovatyj\Contracts\search\SearchDocument;
use Besnovatyj\Contracts\sitemap\SitemapUrl;
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
        return Document::find()->visible()->count();
    }

    public function getAllByRange(int $offset, int $limit): array
    {
        return Document::find()
            ->alias('d')
            ->visible('d')
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function getAllIterator(): iterable
    {
        return Document::find()->alias('d')->visible('d')->each();
    }

    public function getAll(?DocumentFilterForm $filter = null): DataProviderInterface
    {
        $query = Document::find()->alias('d')->visible('d');
        return $this->getProvider($query, $filter);
    }

    public function getAllByCategory(Category $category, ?DocumentFilterForm $filter = null): DataProviderInterface
    {
        $query = Document::find()->alias('d')->visible('d')->with('category');
        $ids = $this->treeScope->descendantIds($category, andSelf: true);
        $query->andWhere(['d.category_id' => $ids]);
        $query->groupBy('d.id');
        return $this->getProvider($query, $filter);
    }

    public function find($id): ?Document
    {
        /** @var $documents Document */
        $documents = Document::find()->visible()->andWhere(['id' => $id])->one();
        return $documents;
    }

    /**
     * Документы для сквозного поиска — только публично доступные ({@see DocumentsQuery::visible()}).
     *
     * Генератор с чтением пачками: полная переиндексация не должна держать в памяти весь архив.
     * Поля отдаются СЫРЫМИ — нормализация текста едина для всех модулей и выполняется модулем поиска.
     *
     * В ключевые слова уходит то, по чему документ ищут, но чего нет в названии: категория,
     * оригинальное имя файла и расширение («приказ pdf», «смета xlsx»).
     *
     * @return iterable<SearchDocument>
     */
    public function searchDocuments(): iterable
    {
        $query = Document::find()->alias('d')->visible('d')
            ->with('category')
            ->orderBy(['d.id' => SORT_ASC]);

        /** @var Document $document */
        foreach ($query->each(100) as $document) {
            $keywords = array_filter([
                $document->category?->name,
                $document->original_name,
                $document->extension,
            ]);

            yield new SearchDocument(
                type: 'documents.document',
                entityId: (int)$document->id,
                route: '/Documents/document/view',
                params: ['id' => (int)$document->id],
                title: (string)$document->title,
                text: (string)$document->description,
                keywords: implode(' ', $keywords),
                // Для документа осмысленна его собственная дата (дата приказа, письма), а не
                // дата записи в базе; при её отсутствии — дата загрузки файла, затем создания.
                // Все три — строковые колонки DATE/DATETIME, поэтому только strtotime().
                date: $this->documentTimestamp($document),
            );
        }
    }

    /**
     * Дата документа для карточки выдачи и сортировки по свежести, в виде Unix-timestamp.
     */
    private function documentTimestamp(Document $document): ?int
    {
        foreach ([$document->document_date, $document->uploaded_at, $document->created_at] as $value) {
            if ($value !== null && $value !== '' && ($timestamp = strtotime((string)$value)) !== false) {
                return $timestamp;
            }
        }

        return null;
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


    /**
     * Документы для карты сайта.
     *
     * Тот же инвариант, что у поиска, — только публично доступное. В карту идёт страница документа,
     * а не файл: скачивание (`/Documents/document/download`) — действие, а не адрес для индекса.
     *
     * `lastmod` — дата изменения ЗАПИСИ, а не дата самого документа: поиску осмысленна дата приказа,
     * а краулеру — «поменялась ли страница».
     *
     * @return iterable<SitemapUrl>
     */
    public function sitemapUrls(): iterable
    {
        $query = Document::find()->alias('d')->visible('d')->orderBy(['d.id' => SORT_DESC]);

        /** @var Document $document */
        foreach ($query->each(200) as $document) {
            yield new SitemapUrl(
                route: '/Documents/document/view',
                params: ['id' => (int)$document->id],
                title: (string)$document->title,
                // updated_at — колонка DATETIME, а контракт ждёт Unix-timestamp.
                lastModified: $document->updated_at === null
                    ? null
                    : (strtotime((string)$document->updated_at) ?: null),
            );
        }
    }

    /**
     * Отпечаток состояния документов для карты сайта: сколько их и когда правили последний раз.
     *
     * Одного `MAX(updated_at)` мало — он не замечает удаления документа, а удалённая страница
     * обязана исчезнуть из карты. Пара «сколько + когда» это закрывает и стоит одного запроса.
     */
    public function sitemapRevision(): string
    {
        $row = Document::find()->alias('d')->visible('d')
            ->select(['total' => 'COUNT(*)', 'latest' => 'MAX(d.updated_at)'])
            ->asArray()
            ->one();

        return ((string)($row['total'] ?? '0')) . ':' . ((string)($row['latest'] ?? ''));
    }
}
