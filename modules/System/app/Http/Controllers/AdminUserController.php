<?php

namespace Modules\System\App\Http\Controllers;

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
            ->with(['department', 'roles', 'posts'])
            ->orderByDesc('id');

        if ($request->filled('username')) {
            $query->where('username', 'like', '%'.$request->get('username').'%');
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
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', Rule::exists(Role::class, 'id')],
            'post_ids' => ['sometimes', 'array'],
            'post_ids.*' => ['integer', Rule::exists(Post::class, 'id')],
        ]);

        $roleIds = $data['role_ids'] ?? [];
        $postIds = $data['post_ids'] ?? [];
        unset($data['role_ids'], $data['post_ids']);

        $admin = Admin::query()->create($data);

        if ($roleIds !== []) {
            $admin->roles()->sync($roleIds);
        }
        if ($postIds !== []) {
            $admin->posts()->sync($postIds);
        }

        $admin->load(['roles', 'posts', 'department']);

        return ApiResponse::success($admin, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Admin::query()->findOrFail($id);

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
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', Rule::exists(Role::class, 'id')],
            'post_ids' => ['sometimes', 'array'],
            'post_ids.*' => ['integer', Rule::exists(Post::class, 'id')],
        ]);

        $roleIds = $data['role_ids'] ?? null;
        $postIds = $data['post_ids'] ?? null;
        unset($data['role_ids'], $data['post_ids']);

        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }
        unset($data['password']);

        $admin->fill($data);
        $admin->save();

        if (is_array($roleIds)) {
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
        Admin::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }
}
