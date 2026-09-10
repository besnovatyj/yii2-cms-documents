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
use Besnovatyj\Contracts\search\SearchSource;
use Besnovatyj\Contracts\search\SearchableProvider;
use Besnovatyj\Documents\entities\Category;
use Besnovatyj\Documents\readModels\CategoryReadRepository;
use Besnovatyj\Documents\readModels\DocumentsReadRepository;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;

class Module extends CmsModule implements
    DeclaresModule, ProvidesAdminMenu,
    ProvidesDirectories, ProvidesMigrations, MenuTargetProvider, SearchableProvider
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
        return (new TreeQueryScope(Category::class))->dropdownTree(keyAttribute: 'slug', indent: '— ');
    }

    /**
     * Контент модуля для сквозного поиска. Реализация {@see SearchableProvider}; вызывается
     * только модулем поиска, если он установлен.
     *
     * @return SearchSource[]
     */
    public function searchSources(): array
    {
        return [
            new SearchSource('documents.document', 'Документы', 1.0, 'bi bi-file-earmark-text'),
            new SearchSource('documents.category', 'Категории документов', 0.7, 'bi bi-folder'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function searchDocuments(string $type): iterable
    {
        return match ($type) {
            'documents.document' => (new DocumentsReadRepository())->searchDocuments(),
            'documents.category' => (new CategoryReadRepository())->searchDocuments(),
            default => [],
        };
    }

}
