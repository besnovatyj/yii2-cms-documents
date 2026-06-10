<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\forms\backend;

use Besnovatyj\Forms\CompositeForm;
use Besnovatyj\Meta\MetaForm;
use Besnovatyj\Helpers\StringHelper;
use Besnovatyj\Validators\SlugValidator;
use Besnovatyj\Documents\entities\Category;
use Besnovatyj\TreeManager\Manager\forms\TreeNodeFormInterface;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;
use yii\helpers\Inflector;

/**
 * @property MetaForm $meta;
 */
class CategoryForm extends CompositeForm implements TreeNodeFormInterface
{
    // Глобальные свойства
    public int|string|null $nodeId = null {
        get {
            return $this->nodeId;
        }
    }    // Идентификатор редактируемого узла
    public int|string|null $parentId = null {
        get {
            return $this->parentId;
        }
    }  // Родитель
    public int|string $status = 0 {
        get {
            return $this->status;
        }
    }            // статус активности

    // Локальные свойства
    public string $name = '';
    public string $slug = '';
    public string $description = '';

    private ?Category $_category = null;

    public function __construct(?Category $category = null, ?int $parentId = null, $config = [])
    {
        // TODO - решить что-то с этим $parentId, как-то криво это всё
        $this->parentId = $parentId; // При создании в виде дочернего
        if ($category) {
            $this->nodeId = $category->id;
            $this->name = $category->name;
            $this->slug = $category->slug;
            $this->description = $category->description;
            $this->meta = new MetaForm($category->meta);
            $this->_category = $category;
        } else {
            $this->meta = new MetaForm();
        }
        parent::__construct($config);
    }

    public function beforeValidate(): bool
    {
        $this->status = (int)$this->status;
        $this->nodeId = (int)$this->nodeId;
        $this->parentId = (int)$this->parentId;

        $this->name = StringHelper::spaceReplace($this->name);
        $this->slug = $this->slug ? Inflector::slug($this->slug) : Inflector::slug($this->name);
        $this->description = StringHelper::spaceReplace($this->description);
        return parent::beforeValidate();
    }

    public function rules(): array
    {
        return [
            [['name', 'status'], 'required'],
            [['name', 'slug'], 'string', 'max' => 255],
            [['status', 'parentId', 'nodeId'], 'integer'],
            ['status', 'in', 'range' => [0, 1]],
            [['description'], 'string'],
            ['slug', SlugValidator::class],
            [['name', 'slug'], 'unique', 'targetClass' => Category::class, 'filter' => $this->_category ? ['<>', 'id', $this->_category->id] : null]
        ];
    }

    // TODO - Методы создания выпадающих списков не здесь должны лежать?

//    public function parentCategoriesList(): array
//    {
//        return ArrayHelper::map(Category::find()->orderBy('lft')->asArray()->all(), 'id', static function (array $category) {
//            return ($category['depth'] > 0 ? str_repeat('⚬ ', $category['depth']) . ' ' : '') . $category['name'];
//        });
//    }

    public function parentCategoriesList(): array
    {
        $scope = new TreeQueryScope(Category::class);
        return $scope->dropdownTree(excludeNodeId: $this->nodeId ? (int)$this->nodeId : null);
    }

    public function internalForms(): array
    {
        return ['meta'];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название категории',
            'slug' => 'Slug (если не вписывать, заполнится автоматически)',
            'description' => 'Описание',
            'parentId' => 'Родительская категория',
        ];
    }

    public function isNewRecord(): bool
    {
        return $this->_category !== null;
    }

    // Для того чтобы упростить поиск полей в сервисе TreeControllerTrait
    public function formName(): string
    {
        return '';
    }

}
