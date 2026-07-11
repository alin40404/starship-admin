<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Services\AdminPermissionService;
use Modules\System\App\Services\DataScopeService;
use Modules\System\App\Support\DepartmentPermissions;
use Modules\System\App\Support\RolePermissions;

class DepartmentController extends Controller
{
    public function tree(Request $request): JsonResponse
    {
        $query = Department::query()
            ->with('leader:id,nickname,real_name,username')
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
        $query = Department::query()
            ->with('leader:id,nickname,real_name,username')
            ->orderBy('sort')
            ->orderBy('id');

        $admin = $request->user();
        if ($admin instanceof Admin) {
            app(DataScopeService::class)->applyDepartmentScope($query, $admin);
        }

        $paginator = $query->paginate((int) $request->get('per_page', 15));
        $paginator->getCollection()->transform(fn (Department $dept) => $this->formatDepartment($dept));

        return ApiResponse::success($paginator);
    }

    public function show(int $id): JsonResponse
    {
        $dept = Department::query()
            ->with('leader:id,nickname,real_name,username')
            ->findOrFail($id);

        return ApiResponse::success($this->formatDepartment($dept));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'name' => ['required', 'string', 'max:100'],
            'leader_id' => ['nullable', 'integer', Rule::exists(Admin::class, 'id')],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $dept = Department::query()->create($data);

        return ApiResponse::success(
            $this->formatDepartment($dept->load('leader:id,nickname,real_name,username')),
            '创建成功',
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dept = Department::query()->findOrFail($id);
        $data = $request->validate([
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'name' => ['sometimes', 'string', 'max:100'],
            'leader_id' => ['nullable', 'integer', Rule::exists(Admin::class, 'id')],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['parent_id']) && (int) $data['parent_id'] === (int) $dept->id) {
            return ApiResponse::error('上级部门不能为自身', 422);
        }

        $dept->update($data);

        return ApiResponse::success(
            $this->formatDepartment($dept->fresh()->load('leader:id,nickname,real_name,username')),
            '更新成功',
        );
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $dept = Department::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $dept->update($data);

        return ApiResponse::success(
            $this->formatDepartment($dept->fresh()->load('leader:id,nickname,real_name,username')),
            '状态已更新',
        );
    }

    public function updateSort(Request $request, int $id): JsonResponse
    {
        $dept = Department::query()->findOrFail($id);
        $data = $request->validate([
            'sort' => ['required', 'integer', 'min:0'],
        ]);

        $dept->update($data);

        return ApiResponse::success(
            $this->formatDepartment($dept->fresh()->load('leader:id,nickname,real_name,username')),
            '排序已更新',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $dept = Department::query()->whereKey($id)->firstOrFail();

        if (Department::query()->where('parent_id', $dept->id)->exists()) {
            return ApiResponse::error('存在下级部门，无法删除', 422);
        }

        if (Admin::query()->where('dept_id', $dept->id)->exists()) {
            return ApiResponse::error('部门下存在管理员，无法删除', 422);
        }

        $dept->delete();

        return ApiResponse::success(null, '已删除');
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deletableIds = [];
        foreach ($ids as $id) {
            if (Department::query()->where('parent_id', $id)->exists()) {
                continue;
            }
            if (Admin::query()->where('dept_id', $id)->exists()) {
                continue;
            }
            $deletableIds[] = (int) $id;
        }

        if ($deletableIds === []) {
            return ApiResponse::error('所选部门存在下级或管理员，无法删除', 422);
        }

        $deleted = Department::query()->whereIn('id', $deletableIds)->delete();
        $skipped = count($ids) - count($deletableIds);

        return ApiResponse::success(
            ['deleted' => $deleted, 'skipped' => $skipped],
            $skipped > 0 ? "已删除 {$deleted} 个，{$skipped} 个因存在下级或管理员未删除" : '已批量删除',
        );
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $updated = Department::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], '已批量更新状态');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Department>  $rows
     * @return array<int, mixed>
     */
    private function buildTree($rows, int $parentId = 0): array
    {
        $branch = [];

        foreach ($rows->where('parent_id', $parentId) as $item) {
            $formatted = $this->formatDepartment($item);
            $branch[] = [
                ...$formatted,
                'children' => $this->buildTree($rows, (int) $item->id),
            ];
        }

        return $branch;
    }

    private function formatDepartment(Department $dept): array
    {
        $dept->loadMissing('leader:id,nickname,real_name,username');
        $leader = $dept->leader;

        return [
            'id' => $dept->id,
            'parent_id' => $dept->parent_id,
            'name' => $dept->name,
            'leader_id' => $dept->leader_id,
            'leader' => $leader ? [
                'id' => $leader->id,
                'nickname' => $leader->nickname,
                'real_name' => $leader->real_name,
                'username' => $leader->username,
            ] : null,
            'leader_name' => $this->formatLeaderName($leader),
            'phone' => $dept->phone,
            'email' => $dept->email,
            'sort' => $dept->sort,
            'status' => $dept->status,
            'remark' => $dept->remark,
            'created_at' => $dept->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $dept->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function formatLeaderName(?Admin $leader): ?string
    {
        if (! $leader) {
            return null;
        }

        $name = trim((string) ($leader->real_name ?: $leader->nickname ?: ''));

        return $name !== '' ? $name : $leader->username;
    }

    private function canLoadUnscopedDeptTree(Admin $admin): bool
    {
        if ((int) $admin->is_super === 1) {
            return true;
        }

        $service = app(AdminPermissionService::class);

        return $service->hasAnyPermission($admin, [
            DepartmentPermissions::QUERY,
            DepartmentPermissions::CREATE,
            DepartmentPermissions::UPDATE,
            DepartmentPermissions::APPEND,
            RolePermissions::CREATE,
            RolePermissions::UPDATE,
        ]);
    }
}
