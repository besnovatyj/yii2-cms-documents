<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents;

use common\components\module\BaseModule;

class Module extends BaseModule
{
    public const bool EDITABLE = true;

    public static function getAdminMenu(): array
    {
        return require __DIR__ . '/config/adminMenu.php';
    }

    public static function getConfig(): array
    {
        return require __DIR__ . '/config/config.php';
    }

    public static function getOptions(): array
    {
        return require __DIR__ . '/config/options.php';
    }

    public static function getDependencies(): array
    {
        return require __DIR__ . '/config/dependencies.php';
    }

    public static function setContainerConfig()
    {
        return (require __DIR__ . '/config/container.php')(\Yii::$container);
    }

}
