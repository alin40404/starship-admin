<?php

namespace Modules\Cms\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cms\App\Support\CmsPermissions;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Role;
use Modules\System\Database\Seeders\Concerns\SeedsMenus;

class CmsMenuSeeder extends Seeder
{
    use SeedsMenus;

    public function run(): void
    {
        $root = Menu::query()->firstOrCreate(
            ['path' => '/cms', 'type' => 1],
            [
                'parent_id' => 0,
                'name' => 'CmsCenter',
                'title' => '内容管理',
                'component' => null,
                'permission' => null,
                'icon' => 'ep:document-copy',
                'sort' => 70,
                'other_settings' => $this->menuOther(1),
                'status' => 1,
            ],
        );

        $categories = Menu::query()->firstOrCreate(
            ['path' => '/cms/categories', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'CmsCategories',
                'title' => '栏目管理',
                'component' => '/cms/categories/index',
                'permission' => CmsPermissions::CATEGORIES_QUERY,
                'icon' => 'ep:folder',
                'sort' => 71,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $articles = Menu::query()->firstOrCreate(
            ['path' => '/cms/articles', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'CmsArticles',
                'title' => '文章管理',
                'component' => '/cms/articles/index',
                'permission' => CmsPermissions::ARTICLES_QUERY,
                'icon' => 'ep:edit',
                'sort' => 72,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $menuIds = [(int) $root->id, (int) $categories->id, (int) $articles->id];

        foreach (CmsPermissions::categoryButtonDefinitions() as $btn) {
            $button = Menu::query()->firstOrCreate(
                [
                    'parent_id' => $categories->id,
                    'type' => 3,
                    'permission' => $btn['permission'],
                ],
                [
                    'name' => $btn['name'],
                    'title' => $btn['title'],
                    'path' => null,
                    'component' => null,
                    'icon' => null,
                    'sort' => $btn['sort'],
                    'other_settings' => $this->menuOther(0),
                    'status' => 1,
                ],
            );
            $menuIds[] = (int) $button->id;
        }

        foreach (CmsPermissions::articleButtonDefinitions() as $btn) {
            $button = Menu::query()->firstOrCreate(
                [
                    'parent_id' => $articles->id,
                    'type' => 3,
                    'permission' => $btn['permission'],
                ],
                [
                    'name' => $btn['name'],
                    'title' => $btn['title'],
                    'path' => null,
                    'component' => null,
                    'icon' => null,
                    'sort' => $btn['sort'],
                    'other_settings' => $this->menuOther(0),
                    'status' => 1,
                ],
            );
            $menuIds[] = (int) $button->id;
        }

        $role = Role::query()->where('code', 'super_admin')->first();
        if ($role !== null) {
            $role->menus()->syncWithoutDetaching($menuIds);
        }
    }
}
