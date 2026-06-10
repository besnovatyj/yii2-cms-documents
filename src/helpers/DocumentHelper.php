<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\helpers;

use Exception;
use Besnovatyj\Documents\entities\Document;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

class DocumentHelper
{
    public static function statusList(): array
    {
        return [
            Document::STATUS_DRAFT => 'Draft',
            Document::STATUS_ACTIVE => 'Active',
        ];
    }

    /**
     * @throws Exception
     */
    public static function statusName($status): string
    {
        return ArrayHelper::getValue(self::statusList(), $status);
    }

    /**
     * @throws Exception
     */
    public static function statusLabel($model): string
    {
        switch ($model->status) {
            case Document::STATUS_DRAFT:
                $class = 'badge bg-secondary';
                $action = 'activate';
                break;
            case Document::STATUS_ACTIVE:
                $class = 'badge bg-success';
                $action = 'draft';
                break;
            default:
                $class = 'badge bg-default';
                $action = 'activate';
        }

        $text = Html::tag('span', ArrayHelper::getValue(self::statusList(), $model->status), [
            'class' => $class,
        ]);
        $url = Url::to([$action, 'id' => $model->id]);
        return Html::a($text, $url, [
            'data' => [
                'method' => 'post',
            ],
        ]);

    }
}
