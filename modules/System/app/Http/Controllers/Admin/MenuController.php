<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Menu;

class MenuController extends Controller
{
    public function tree(): JsonResponse
    {
        $rows = Menu::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return ApiResponse::success($this->buildTree($rows));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Menu::query()->orderBy('sort')->orderBy('id');

        if ($request->filled('type')) {
            $query->where('type', (int) $request->get('type'));
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->menuRules(true));

        $menu = Menu::query()->create($data);

        return ApiResponse::success($menu, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $menu = Menu::query()->findOrFail($id);
        $data = $request->validate($this->menuRules(false));

        $menu->update($data);

        return ApiResponse::success($menu->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        Menu::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }

    /**
     * @return array<string, mixed>
     */
    private function menuRules(bool $isCreate): array
    {
        $typeRule = $isCreate
            ? ['required', 'integer', 'in:1,2,3,4,5']
            : ['sometimes', 'integer', 'in:1,2,3,4,5'];

        return [
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'type' => $typeRule,
            'name' => $isCreate
                ? ['required', 'string', 'max:100']
                : ['sometimes', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:255'],
            'component' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'active_icon' => ['nullable', 'string', 'max:100'],
            'badge_type' => ['nullable', 'string', 'max:20'],
            'badge' => ['nullable', 'string', 'max:50'],
            'badge_variants' => ['nullable', 'string', 'max:30'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'iframe_src' => ['nullable', 'string', 'max:500'],
            'sort' => ['sometimes', 'integer'],
            'visible' => ['sometimes', 'integer', 'in:0,1'],
            'keep_alive' => ['sometimes', 'integer', 'in:0,1'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
            'hide_submenu' => ['sometimes', 'integer', 'in:0,1'],
            'hide_in_breadcrumb' => ['sometimes', 'integer', 'in:0,1'],
            'hide_in_tab' => ['sometimes', 'integer', 'in:0,1'],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Menu>  $rows
     * @return array<int, mixed>
     */
    private function buildTree($rows, int $parentId = 0): array
    {
        $branch = [];

        foreach ($rows->where('parent_id', $parentId) as $item) {
            $branch[] = [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'type' => $item->type,
                'name' => $item->name,
                'title' => $item->title,
                'path' => $item->path,
                'component' => $item->component,
                'permission' => $item->permission,
                'icon' => $item->icon,
                'active_icon' => $item->active_icon,
                'badge_type' => $item->badge_type,
                'badge' => $item->badge,
                'badge_variants' => $item->badge_variants,
                'link_url' => $item->link_url,
                'iframe_src' => $item->iframe_src,
                'sort' => $item->sort,
                'visible' => $item->visible,
                'keep_alive' => $item->keep_alive,
                'status' => $item->status,
                'remark' => $item->remark,
                'hide_submenu' => $item->hide_submenu,
                'hide_in_breadcrumb' => $item->hide_in_breadcrumb,
                'hide_in_tab' => $item->hide_in_tab,
                'children' => $this->buildTree($rows, (int) $item->id),
            ];
        }

        return $branch;
    }
}
