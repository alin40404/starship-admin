<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Role::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->get('name').'%');
        }
        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->get('code').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = [
            'id' => 'id',
            'name' => 'name',
            'code' => 'code',
            'data_scope' => 'data_scope',
            'sort' => 'sort',
            'status' => 'status',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderBy('id');
        } else {
            $query->orderBy('sort')->orderBy('id');
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::query()->findOrFail($id);
        $role->load('menus');
        $isSuperAdminRole = $role->code === 'super_admin';

        $menuIds = $isSuperAdminRole
            ? Menu::query()->pluck('id')->values()->all()
            : $role->menus->pluck('id')->values()->all();

        return ApiResponse::success([
            'id' => $role->id,
            'name' => $role->name,
            'code' => $role->code,
            'data_scope' => $role->data_scope,
            'sort' => $role->sort,
            'status' => $role->status,
            'remark' => $role->remark,
            'menu_ids' => $menuIds,
        ]);
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

        if ($role->code === 'super_admin') {
            return ApiResponse::error('系统内置角色不允许修改', 422);
        }

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
        $role = Role::query()->whereKey($id)->firstOrFail();

        if ($role->code === 'super_admin') {
            return ApiResponse::error('系统内置角色不允许删除', 422);
        }

        $role->delete();

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
