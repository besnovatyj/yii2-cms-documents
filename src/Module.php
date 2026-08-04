<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents;

use Besnovatyj\Kernel\module\CmsModule;
use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesAdminMenu;
use Besnovatyj\Contracts\module\ProvidesDirectories;
use Besnovatyj\Contracts\module\ProvidesMigrations;
use Besnovatyj\Contracts\menu\MenuTarget;
use Besnovatyj\Contracts\menu\MenuTargetProvider;
use Besnovatyj\Documents\readModels\CategoryReadRepository;

class Module extends CmsModule implements
    DeclaresModule, ProvidesAdminMenu,
    ProvidesDirectories, ProvidesMigrations, MenuTargetProvider
{
    public const bool EDITABLE = true;
    public const string VERSION = '1.0.0';
    public const string MODULE_ID = 'Documents';

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function moduleVersion(): string { return self::VERSION; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function adminMenu(): array { return require __DIR__.'/config/adminMenu.php'; }
    public static function moduleConfig(): array { return require __DIR__.'/config/config.php'; }
    public static function migrationPath(): string { return __DIR__.'/migrations'; }
    public static function migrationNamespace(): ?string { return __NAMESPACE__.'\\migrations'; }
    public static function directories(): array { return ['@static/origin/Documents','@static/cache/Documents'];}

    /**
     * Цели для построения пунктов меню. Реализация {@see MenuTargetProvider};
     * вызывается только модулем меню, если он установлен.
     *
     * @return MenuTarget[]
     */
    public function menuTargets(): array
    {
        return [
            new MenuTarget('/Documents/document/category', 'Категория документов', 'slug'),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string,string>
     */
    public function menuCandidates(string $route): array
    {
        return match (ltrim($route, '/')) {
            'Documents/document/category' => $this->categorySlugMap(),
            default => [],
        };
    }

    /**
     * Карта `slug => подпись` (с отступом по глубине дерева) для категорий документов.
     *
     * @return array<string,string>
     */
    private function categorySlugMap(): array
    {
        $map = [];
        foreach ((new CategoryReadRepository())->getAll() as $category) {
            $prefix = $category->depth > 0 ? str_repeat('— ', (int)$category->depth) : '';
            $map[$category->slug] = $prefix . $category->name;
        }
        return $map;
    }

}
