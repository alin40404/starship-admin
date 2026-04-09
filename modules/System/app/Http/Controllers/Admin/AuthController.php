<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\System\App\Models\Admin;

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

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'admin' => $this->formatAdmin($admin),
        ], '登录成功');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, '已退出');
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user();
        $admin?->load(['roles', 'posts', 'department']);

        return ApiResponse::success($admin ? $this->formatAdmin($admin) : null);
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
