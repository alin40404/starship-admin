<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\Post;
use Modules\System\App\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Admin::query()
            ->with(['department', 'roles', 'posts']);

        if ($request->filled('username')) {
            $query->where('username', 'like', '%'.$request->get('username').'%');
        }
        if ($request->filled('name')) {
            $keyword = (string) $request->get('name');
            $query->where(function ($q) use ($keyword): void {
                $q->where('real_name', 'like', '%'.$keyword.'%')
                    ->orWhere('nickname', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->get('email').'%');
        }
        if ($request->filled('mobile')) {
            $query->where('mobile', 'like', '%'.$request->get('mobile').'%');
        }
        if ($request->filled('dept_name')) {
            $keyword = (string) $request->get('dept_name');
            $query->whereHas('department', function ($dept) use ($keyword): void {
                $dept->where('name', 'like', '%'.$keyword.'%');
            });
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = [
            'id' => 'id',
            'username' => 'username',
            'nickname' => 'nickname',
            'status' => 'status',
            'dept_id' => 'dept_id',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder);
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique(Admin::class, 'username')],
            'password' => ['required', 'string', Password::defaults()],
            'nickname' => ['nullable', 'string', 'max:100'],
            'real_name' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'dept_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'is_super' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', Rule::exists(Role::class, 'id')],
            'post_ids' => ['sometimes', 'array'],
            'post_ids.*' => ['integer', Rule::exists(Post::class, 'id')],
        ]);

        $roleIds = $data['role_ids'] ?? [];
        $postIds = $data['post_ids'] ?? [];
        unset($data['role_ids'], $data['post_ids']);

        $admin = Admin::query()->create($data);

        $admin->roles()->sync($roleIds);
        if ($postIds !== []) {
            $admin->posts()->sync($postIds);
        }

        $admin->load(['roles', 'posts', 'department']);

        return ApiResponse::success($admin, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Admin::query()->findOrFail($id);
        $isSuper = (int) $admin->is_super === 1;

        $data = $request->validate([
            'password' => ['nullable', 'string', Password::defaults()],
            'nickname' => ['nullable', 'string', 'max:100'],
            'real_name' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'dept_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'is_super' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
            'role_ids' => $isSuper
                ? ['sometimes', 'array']
                : ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', Rule::exists(Role::class, 'id')],
            'post_ids' => ['sometimes', 'array'],
            'post_ids.*' => ['integer', Rule::exists(Post::class, 'id')],
        ]);

        $roleIds = $data['role_ids'] ?? null;
        $postIds = $data['post_ids'] ?? null;
        unset($data['role_ids'], $data['post_ids']);

        if ($isSuper) {
            unset($data['status']);
        }

        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }
        unset($data['password']);

        $admin->fill($data);
        $admin->save();

        if (is_array($roleIds) && ! $isSuper) {
            $admin->roles()->sync($roleIds);
        }
        if (is_array($postIds)) {
            $admin->posts()->sync($postIds);
        }

        $admin->load(['roles', 'posts', 'department']);

        return ApiResponse::success($admin->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $admin = Admin::query()->whereKey($id)->firstOrFail();

        if ((int) $admin->is_super === 1) {
            return ApiResponse::error('超级管理员不允许删除', 422);
        }

        $admin->delete();

        return ApiResponse::success(null, '已删除');
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $hasSuper = Admin::query()
            ->whereIn('id', $ids)
            ->where('is_super', 1)
            ->exists();

        if ($hasSuper) {
            return ApiResponse::error('所选记录中包含超级管理员，无法删除', 422);
        }

        $deleted = Admin::query()
            ->whereIn('id', $ids)
            ->where('is_super', 0)
            ->delete();

        return ApiResponse::success(['deleted' => $deleted], '已批量删除');
    }
}
