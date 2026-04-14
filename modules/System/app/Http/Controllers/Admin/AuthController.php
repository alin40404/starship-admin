<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\System\App\Models\Admin;
use Modules\System\App\Services\AdminPermissionService;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $admin = Admin::query()->where('username', $validated['username'])->first();

        if (! $admin || (int) $admin->status !== 1 || ! Hash::check($validated['password'], $admin->password)) {
            return ApiResponse::error('用户名或密码错误', 401);
        }

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $token = $admin->createToken('admin')->plainTextToken;

        $permission = app(AdminPermissionService::class);

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'admin' => $this->formatAdmin($admin),
            'permissions' => $permission->permissionCodes($admin),
            'menus' => $permission->menuTreeForAdmin($admin),
        ], '登录成功');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, '已退出');
    }

    public function me(Request $request, AdminPermissionService $permission): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::success(null);
        }

        $admin->load(['roles', 'posts', 'department']);

        $data = $this->formatAdmin($admin);
        $data['permissions'] = $permission->permissionCodes($admin);

        return ApiResponse::success($data);
    }

    public function menus(Request $request, AdminPermissionService $permission): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error('未登录', 401);
        }

        return ApiResponse::success($permission->menuTreeForAdmin($admin));
    }

    public function permissions(Request $request, AdminPermissionService $permission): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error('未登录', 401);
        }

        return ApiResponse::success([
            'permissions' => $permission->permissionCodes($admin),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error('未登录', 401);
        }

        $data = $request->validate([
            'nickname' => ['nullable', 'string', 'max:100'],
            'real_name' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:100', Rule::unique(Admin::class, 'email')->ignore($admin->id)],
            'mobile' => ['required', 'regex:/^1[3-9]\d{9}$/', Rule::unique(Admin::class, 'mobile')->ignore($admin->id)],
        ]);

        $admin->fill($data);
        $admin->save();

        return ApiResponse::success($this->formatAdmin($admin->fresh()), '个人信息已更新');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error('未登录', 401);
        }

        $data = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::defaults()],
        ]);

        $oldPassword = trim((string) $data['old_password']);
        $newPassword = trim((string) $data['new_password']);

        if (! Hash::check($oldPassword, $admin->password)) {
            return ApiResponse::error('旧密码不正确', 422);
        }

        if (Hash::check($newPassword, $admin->password)) {
            return ApiResponse::error('新密码不能与旧密码相同', 422);
        }

        $admin->password = Hash::make($newPassword);
        $admin->save();

        return ApiResponse::success(null, '密码修改成功');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAdmin(Admin $admin): array
    {
        return [
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'real_name' => $admin->real_name,
            'avatar' => $admin->avatar,
            'email' => $admin->email,
            'mobile' => $admin->mobile,
            'dept_id' => $admin->dept_id,
            'status' => $admin->status,
            'is_super' => $admin->is_super,
            'department' => $admin->relationLoaded('department') ? $admin->department : null,
            'roles' => $admin->relationLoaded('roles') ? $admin->roles->map->only(['id', 'name', 'code']) : [],
            'posts' => $admin->relationLoaded('posts') ? $admin->posts->map->only(['id', 'name', 'code']) : [],
        ];
    }
}
