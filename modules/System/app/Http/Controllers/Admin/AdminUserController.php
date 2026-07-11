<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\Post;
use Modules\System\App\Models\Role;
use Modules\System\App\Services\DataScopeService;
use Modules\System\App\Support\AdminRuntimeConfig;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Admin::query()
            ->with(['department', 'roles', 'posts']);

        $admin = $request->user();
        if ($admin instanceof Admin) {
            app(DataScopeService::class)->applyAdminScope($query, $admin);
        }

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('username', 'like', '%'.$keyword.'%')
                    ->orWhere('real_name', 'like', '%'.$keyword.'%')
                    ->orWhere('nickname', 'like', '%'.$keyword.'%');
            });
        }
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
        if ($request->filled('dept_id')) {
            $query->where('dept_id', (int) $request->get('dept_id'));
        }
        if ($request->filled('dept_name')) {
            $keyword = (string) $request->get('dept_name');
            $query->whereHas('department', function ($dept) use ($keyword): void {
                $dept->where('name', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('updated_start')) {
            $query->where(
                'updated_at',
                '>=',
                Carbon::parse((string) $request->get('updated_start'))->startOfDay(),
            );
        }
        if ($request->filled('updated_end')) {
            $query->where(
                'updated_at',
                '<=',
                Carbon::parse((string) $request->get('updated_end'))->endOfDay(),
            );
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = [
            'id' => 'id',
            'username' => 'username',
            'nickname' => 'nickname',
            'status' => 'status',
            'dept_id' => 'dept_id',
            'updated_at' => 'updated_at',
            'created_at' => 'created_at',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder);
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique(Admin::class, 'username')],
            'password' => ['required', 'string', Password::defaults()],
            'nickname' => ['nullable', 'string', 'max:100'],
            'real_name' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:100', Rule::unique(Admin::class, 'email')],
            'mobile' => ['required', 'regex:/^1[3-9]\d{9}$/', Rule::unique(Admin::class, 'mobile')],
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

        return ApiResponse::success($admin, __('system::messages.created'));
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
            'email' => ['required', 'email', 'max:100', Rule::unique(Admin::class, 'email')->ignore($id)],
            'mobile' => ['required', 'regex:/^1[3-9]\d{9}$/', Rule::unique(Admin::class, 'mobile')->ignore($id)],
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

        return ApiResponse::success($admin->fresh(), __('system::messages.updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $admin = Admin::query()->findOrFail($id);

        if ((int) $admin->is_super === 1) {
            return ApiResponse::error(__('system::messages.super_admin_status_readonly'), 422);
        }

        $data = $request->validate([
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $admin->update($data);

        return ApiResponse::success($admin->fresh(), __('system::messages.status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $admin = Admin::query()->whereKey($id)->firstOrFail();

        if ((int) $admin->is_super === 1) {
            return ApiResponse::error(__('system::messages.super_admin_no_delete'), 422);
        }

        $admin->delete();

        return ApiResponse::success(null, __('system::messages.deleted'));
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
            return ApiResponse::error(__('system::messages.super_admin_in_selection_delete'), 422);
        }

        $deleted = Admin::query()
            ->whereIn('id', $ids)
            ->where('is_super', 0)
            ->delete();

        return ApiResponse::success(['deleted' => $deleted], __('system::messages.batch_deleted'));
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $hasSuper = Admin::query()
            ->whereIn('id', $data['ids'])
            ->where('is_super', 1)
            ->exists();

        if ($hasSuper) {
            return ApiResponse::error(__('system::messages.super_admin_in_selection_status'), 422);
        }

        $updated = Admin::query()
            ->whereIn('id', $data['ids'])
            ->where('is_super', 0)
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], __('system::messages.batch_status_updated'));
    }
}
