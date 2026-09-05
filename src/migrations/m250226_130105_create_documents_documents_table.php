<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\migrations;

use Besnovatyj\Kernel\migration\BaseMigration;
use yii\base\NotSupportedException;
use yii\db\Exception;

/** 'm<YYMMDD_HHMMSS>_<n>' */
class m250226_130105_create_documents_documents_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%documents_documents}}';

    /**
     * @throws Exception
     */
    public function safeUp(): void
    {
        parent::safeUp();

        if ($this->existTable(static::TABLE_NAME)) {
            return;
        }

        $this->createTable(static::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull()
                ->comment('Название документа (вводит пользователь)'),
            'description' => $this->text()->null()
                ->comment('Описание документа'),
            'type' => $this->string(255)->notNull()
                ->comment('Тип документа: file или link'),
            'external_url' => $this->string(255)->null()
                ->comment('Внешняя ссылка (если это ссылка на файлообменник)'),
            'original_filename' => $this->string(255)->null()
                ->comment('Ключ хранения: санитизированное имя файла на диске. Включая расширение.'),
            'original_name' => $this->string(255)->null()
                ->comment('Оригинальное имя файла как его загрузил пользователь (для отдачи при скачивании). Включая расширение.'),
            'extension' => $this->string(32)->null()
                ->comment('Расширение файла без точки (например: pdf, rar, docx)'),
            'mime_type' => $this->string(255)->null()
                ->comment('MIME-тип файла (например, application/pdf, image/jpeg)'),
            'file_size' => $this->integer(10)->null()
                ->comment('Размер файла в байтах'),
            'manifest_json' => $this->text()->null()
                ->comment('JSON-манифест содержимого архива (см. ArchiveManifest); NULL — не архив либо содержимое нечитаемо'),
            'category_id' => $this->integer(10)->notNull()
                ->comment('Идентификатор категории'),
            'uploaded_at' => $this->dateTime()->null()
                ->comment('Дата загрузки документа в UTC: проставляется автоматически при создании, редактируется вручную'),
            'document_date' => $this->date()->null()
                ->comment('Дата создания самого документа (дата приказа, письма и т.п.); без времени и часового пояса'),
            'created_at' => $this->dateTime()->null()->defaultExpression('NOW()')
                ->comment('Дата создания записи'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('NOW()')->append('ON UPDATE NOW()')
                ->comment('Дата обновления записи'),
            'status' => $this->smallInteger(1)->notNull()->defaultValue(0)
                ->comment('Статус отображения'),
            'meta_json' => $this->text()->notNull()
                ->comment('JSON of meta-obj'),
        ], $this->tableOptions);
        $this->addCommentOnTable(static::TABLE_NAME, 'Документы');

        // Индексы под сортировку и фильтрацию списка на фронтенде.
        $this->createIndexes(static::TABLE_NAME, 'title');
        $this->createIndexes(static::TABLE_NAME, 'uploaded_at');
        $this->createIndexes(static::TABLE_NAME, 'document_date');

        \Yii::$app->getDb()->createCommand("SET foreign_key_checks = 0")->execute();
        $this->createFKs(static::TABLE_NAME, 'category_id', m250226_130100_create_documents_categories_table::TABLE_NAME, 'id', 'CASCADE');
        \Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();

        parent::safeUp();
    }

}
