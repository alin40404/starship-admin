<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Services\AdminPermissionService;
use Modules\System\App\Services\DataScopeService;
use Modules\System\App\Support\RolePermissions;

class DepartmentController extends Controller
{
    public function tree(Request $request): JsonResponse
    {
        $query = Department::query()
            ->orderBy('sort')
            ->orderBy('id');

        $admin = $request->user();
        if (
            $admin instanceof Admin
            && ! ($request->boolean('unscoped') && $this->canLoadUnscopedDeptTree($admin))
        ) {
            app(DataScopeService::class)->applyDepartmentScope($query, $admin);
        }

        $rows = $query->get();

        return ApiResponse::success($this->buildTree($rows));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Department::query()->orderBy('sort')->orderBy('id');

        $admin = $request->user();
        if ($admin instanceof Admin) {
            app(DataScopeService::class)->applyDepartmentScope($query, $admin);
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'name' => ['required', 'string', 'max:100'],
            'leader' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $dept = Department::query()->create($data);

        return ApiResponse::success($dept, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dept = Department::query()->findOrFail($id);
        $data = $request->validate([
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'name' => ['sometimes', 'string', 'max:100'],
            'leader' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $dept->update($data);

        return ApiResponse::success($dept->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        Department::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Department>  $rows
     * @return array<int, mixed>
     */
    private function buildTree($rows, int $parentId = 0): array
    {
        $branch = [];

        foreach ($rows->where('parent_id', $parentId) as $item) {
            $branch[] = [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'name' => $item->name,
                'leader' => $item->leader,
                'phone' => $item->phone,
                'email' => $item->email,
                'sort' => $item->sort,
                'status' => $item->status,
                'remark' => $item->remark,
                'children' => $this->buildTree($rows, (int) $item->id),
            ];
        }

        return $branch;
    }

    private function canLoadUnscopedDeptTree(Admin $admin): bool
    {
        if ((int) $admin->is_super === 1) {
            return true;
        }

        return app(AdminPermissionService::class)->hasAnyPermission($admin, [
            RolePermissions::CREATE,
            RolePermissions::UPDATE,
        ]);
    }
}
