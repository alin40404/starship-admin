<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\System\App\Models\Admin;
use Modules\System\App\Services\AdminPasswordResetService;
use Modules\System\App\Services\AdminPermissionService;
use Modules\System\App\Support\AdminRuntimeConfig;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($validated['username']);
        $admin = Admin::findByLoginIdentifier($identifier);

        if (! $admin || (int) $admin->status !== 1 || ! Hash::check($validated['password'], $admin->password)) {
            $this->writeLoginLog(
                username: $identifier,
                success: false,
                adminId: $admin?->id,
                message: '账号或密码错误',
                request: $request,
            );

            return ApiResponse::error(__('system::messages.invalid_credentials'), 401);
        }

        if (AdminRuntimeConfig::maintenanceEnabled() && (int) $admin->is_super !== 1) {
            $this->writeLoginLog(
                username: $admin->username,
                success: false,
                adminId: $admin->id,
                message: '系统维护中',
                request: $request,
            );

            return ApiResponse::error(__('system::messages.maintenance_mode'), 503);
        }

        if (AdminRuntimeConfig::loginCaptchaEnabled() && ! $request->boolean('captcha')) {
            return ApiResponse::error(__('system::messages.captcha_required'), 422);
        }

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $token = $admin->createToken(
            'admin',
            ['*'],
            now()->addMinutes(AdminRuntimeConfig::jwtTtlMinutes()),
        )->plainTextToken;

        $this->writeLoginLog(
            username: $admin->username,
            success: true,
            adminId: $admin->id,
            message: '登录成功',
            request: $request,
        );

        $permission = app(AdminPermissionService::class);

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'admin' => $this->formatAdmin($admin),
            'permissions' => $permission->permissionCodes($admin),
            'menus' => $permission->menuTreeForAdmin($admin),
        ], __('system::messages.login_success'));
    }

    public function logout(Request $request): JsonResponse
    {
        $admin = $request->user();
        if ($admin instanceof Admin) {
            $this->writeLoginLog(
                username: $admin->username,
                success: true,
                adminId: $admin->id,
                message: '退出登录',
                request: $request,
                action: 'logout',
            );
        }

        $admin?->currentAccessToken()?->delete();

        return ApiResponse::success(null, __('system::messages.logout_success'));
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
            return ApiResponse::error(__('core::api.unauthorized'), 401);
        }

        return ApiResponse::success($permission->menuTreeForAdmin($admin));
    }

    public function permissions(Request $request, AdminPermissionService $permission): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error(__('core::api.unauthorized'), 401);
        }

        return ApiResponse::success([
            'permissions' => $permission->permissionCodes($admin),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error(__('core::api.unauthorized'), 401);
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

        return ApiResponse::success($this->formatAdmin($admin->fresh()), __('system::messages.profile_updated'));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error(__('core::api.unauthorized'), 401);
        }

        $data = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::defaults()],
        ]);

        $oldPassword = trim((string) $data['old_password']);
        $newPassword = trim((string) $data['new_password']);

        if (! Hash::check($oldPassword, $admin->password)) {
            return ApiResponse::error(__('system::messages.old_password_invalid'), 422);
        }

        if (Hash::check($newPassword, $admin->password)) {
            return ApiResponse::error(__('system::messages.new_password_same'), 422);
        }

        $admin->password = Hash::make($newPassword);
        $admin->save();

        return ApiResponse::success(null, __('system::messages.password_changed'));
    }

    public function forgotPassword(Request $request, AdminPasswordResetService $resetService): JsonResponse
    {
        if (! AdminRuntimeConfig::loginForgetPasswordEnabled()) {
            return ApiResponse::error(__('system::messages.password_reset_disabled'), 403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
        ]);

        try {
            $resetService->sendResetLink($validated['email']);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::error(__('system::messages.password_reset_mail_failed'), 500);
        }

        return ApiResponse::success(null, __('system::messages.password_reset_mail_sent'));
    }

    public function verifyResetPassword(Request $request, AdminPasswordResetService $resetService): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'token' => ['required', 'string', 'max:255'],
        ]);

        try {
            $resetService->verifyResetToken($validated['email'], $validated['token']);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(['valid' => true]);
    }

    public function resetPassword(Request $request, AdminPasswordResetService $resetService): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        try {
            $resetService->resetPassword(
                $validated['email'],
                $validated['token'],
                $validated['password'],
            );
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(null, __('system::messages.password_reset_success'));
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

    private function writeLoginLog(
        string $username,
        bool $success,
        ?int $adminId,
        ?string $message,
        Request $request,
        string $action = 'login',
    ): void {
        if (! class_exists(\Modules\Monitor\App\Services\LoginLogWriter::class)) {
            return;
        }

        $writer = app(\Modules\Monitor\App\Services\LoginLogWriter::class);
        if ($action === 'logout' && $adminId !== null) {
            $writer->recordLogout(
                username: $username,
                adminId: $adminId,
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return;
        }

        $writer->recordLogin(
            username: $username,
            success: $success,
            adminId: $adminId,
            message: $message,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }
}
