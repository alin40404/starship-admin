<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\UserGroup;

class UserGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserGroup::query()->withCount('admins');

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('code', 'like', '%'.$keyword.'%');
            });
        }
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
        $group = UserGroup::query()->findOrFail($id);
        $group->load('admins');

        return ApiResponse::success([
            'id' => $group->id,
            'name' => $group->name,
            'code' => $group->code,
            'sort' => $group->sort,
            'status' => $group->status,
            'remark' => $group->remark,
            'admin_ids' => $group->admins->pluck('id')->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', Rule::unique(UserGroup::class, 'code')],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $group = UserGroup::query()->create($data);

        return ApiResponse::success($group, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $group = UserGroup::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:100', Rule::unique(UserGroup::class, 'code')->ignore($group->id)],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $group->update($data);

        return ApiResponse::success($group->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        UserGroup::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = UserGroup::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], '已批量删除');
    }

    public function syncAdmins(Request $request, int $id): JsonResponse
    {
        $group = UserGroup::query()->findOrFail($id);
        $validated = $request->validate([
            'admin_ids' => ['present', 'array'],
            'admin_ids.*' => ['integer', Rule::exists(Admin::class, 'id')],
        ]);

        $group->admins()->sync($validated['admin_ids']);

        return ApiResponse::success(['admin_ids' => $validated['admin_ids']], '成员已更新');
    }
}
