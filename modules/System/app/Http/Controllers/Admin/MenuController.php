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

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'type' => ['required', 'integer', 'in:1,2,3'],
            'name' => ['required', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:255'],
            'component' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'integer'],
            'visible' => ['sometimes', 'integer', 'in:0,1'],
            'keep_alive' => ['sometimes', 'integer', 'in:0,1'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $menu = Menu::query()->create($data);

        return ApiResponse::success($menu, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $menu = Menu::query()->findOrFail($id);
        $data = $request->validate([
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'type' => ['sometimes', 'integer', 'in:1,2,3'],
            'name' => ['sometimes', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:255'],
            'component' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'integer'],
            'visible' => ['sometimes', 'integer', 'in:0,1'],
            'keep_alive' => ['sometimes', 'integer', 'in:0,1'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $menu->update($data);

        return ApiResponse::success($menu->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        Menu::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
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
                'path' => $item->path,
                'component' => $item->component,
                'permission' => $item->permission,
                'icon' => $item->icon,
                'sort' => $item->sort,
                'visible' => $item->visible,
                'keep_alive' => $item->keep_alive,
                'status' => $item->status,
                'remark' => $item->remark,
                'children' => $this->buildTree($rows, (int) $item->id),
            ];
        }

        return $branch;
    }
}
