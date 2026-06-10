<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\forms\backend\search;

use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\helpers\DocumentHelper;
use Besnovatyj\Forms\BaseForm;
use yii\data\ActiveDataProvider;

class DocumentSearch extends BaseForm
{
    public int|null $id = null;
    public string|null $title = null;
    public int|null $category_id = null;
    public int|null $status = null;

    public function rules(): array
    {
        return [
            [['id', 'category_id'], 'integer'],
            ['status', 'in', 'range' => [Document::STATUS_ACTIVE, Document::STATUS_DRAFT]],
            [['title'], 'string'],
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = Document::find()->with('category');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'category_id' => $this->category_id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }

    public function statusList(): array
    {
        return DocumentHelper::statusList();
    }
}
