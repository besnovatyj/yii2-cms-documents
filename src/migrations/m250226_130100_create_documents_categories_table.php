<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\migrations;

use Besnovatyj\Kernel\migration\BaseMigration;
use yii\base\NotSupportedException;

/** 'm<YYMMDD_HHMMSS>_<n>' */
class m250226_130100_create_documents_categories_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%documents_categories}}';

    /**
     * @throws NotSupportedException
     */
    public function safeUp(): void
    {
        parent::safeUp();

        if ($this->existTable(static::TABLE_NAME)) {
            return;
        }

        $this->createTable(static::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'tree' => $this->integer()->null()
                ->comment('Идентификатор дерева'), // TODO Кажется, при переносе веток между деревьями обнуляется, проверить
            'lft' => $this->integer(10)->notNull()
                ->comment('Левый ключ NestedSets'),
            'rgt' => $this->integer(10)->notNull()
                ->comment('Правый ключ NestedSets'),
            'depth' => $this->integer(10)->notNull()
                ->comment('Глубина NestedSets'), // Атрибут не может быть беззнаковым!
            'name' => $this->string(255)->null()->defaultValue("Задайте название категории")
                ->comment('Название категории'),
            'slug' => $this->string(255)->notNull()
                ->comment('Slug категории'),
            'description' => $this->text()->null()
                ->comment('Описание категории'),
            'meta_json' => $this->text()->notNull()
                ->comment('JSON of meta-obj'),
            'status' => $this->smallInteger(1)->notNull()->defaultValue(0)
                ->comment('Статус отображения категории'),
            'sort_order' => $this->integer(10)->notNull()->defaultValue(0)
                ->comment('Сортировка корней'),
        ], $this->tableOptions);
        $this->addCommentOnTable(static::TABLE_NAME, 'Категория документа');

        $this->createIndexes(static::TABLE_NAME, 'depth');
        $this->createIndexes(static::TABLE_NAME, ['tree', 'rgt']);
        $this->createIndexes(static::TABLE_NAME, ['tree', 'lft', 'rgt']);
        $this->createIndexes(static::TABLE_NAME, 'slug', false, true);

        parent::safeUp();
    }

}
