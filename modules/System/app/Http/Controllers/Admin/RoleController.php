<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Role::query()->orderBy('sort')->orderBy('id');

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', Rule::unique(Role::class, 'code')],
            'data_scope' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::query()->create($data);

        return ApiResponse::success($role, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:100', Rule::unique(Role::class, 'code')->ignore($role->id)],
            'data_scope' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $role->update($data);

        return ApiResponse::success($role->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        Role::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }

    public function syncMenus(Request $request, int $id): JsonResponse
    {
        $role = Role::query()->findOrFail($id);
        $validated = $request->validate([
            'menu_ids' => ['required', 'array'],
            'menu_ids.*' => ['integer', 'min:1'],
        ]);

        $role->menus()->sync($validated['menu_ids']);

        return ApiResponse::success(['menu_ids' => $validated['menu_ids']], '菜单权限已更新');
    }
}
