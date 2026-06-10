<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\entities;

use Besnovatyj\Meta\Meta;
use Besnovatyj\Meta\MetaBehavior;
use Besnovatyj\TreeManager\Manager\entities\Node;
use yii\db\ActiveQuery;

/**
 * @property integer $id
 * @property integer $lft
 * @property integer $rgt
 * @property integer $depth
 * @property integer $tree
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $status
 *
 * @property Meta $meta
 * @mixin MetaBehavior
 */
class Category extends Node
{
    public Meta $meta;

    public static function create($name, $slug, $description, Meta $meta): self
    {
        $category = new static();
        $category->name = $name;
        $category->slug = $slug;
        $category->description = $description;
        $category->meta = $meta;
        return $category;
    }

    public function edit($name, $slug, $description, Meta $meta): void
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->meta = $meta;
    }

    public function getSeoTitle(): string
    {
        return $this->meta->title ?: $this->name;
    }

    public static function tableName(): string
    {
        return '{{%documents_categories}}';
    }

    public function countDocumentsByMainTaxonomy(): bool|int|string|null
    {
        return $this->getDocuments()->count();
    }

    public function getDocuments(): ActiveQuery
    {
        return $this->hasMany(Document::class, ['category_id' => 'id']);
    }

    public function behaviors(): array
    {
        return [
            MetaBehavior::class,
            ...parent::behaviors()
        ];
    }

}
