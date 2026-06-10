<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\repositories;

use Besnovatyj\Documents\entities\Category;
use RuntimeException;
use Throwable;
use yii\db\Exception;
use yii\db\StaleObjectException;

class CategoryRepository
{

    public function get($id): Category
    {
        if (!$category = Category::findOne($id)) {
            throw new NotFoundException('Category is not found.');
        }
        return $category;
    }

    /**
     * @throws Exception
     */
    public function save(Category $category): void
    {
        if (!$category->save()) {
            throw new RuntimeException('Saving error.');
        }
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function remove(Category $category): void
    {
        if (!$category->delete()) {
            throw new RuntimeException('Removing error.');
        }
    }
}
