<?php

namespace Modules\System\App\Services;

use Illuminate\Support\Str;
use Modules\System\App\Models\LoginLog;

class LoginLogWriter
{
    public function recordLogin(
        string $username,
        bool $success,
        ?int $adminId = null,
        ?string $message = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): void {
        LoginLog::query()->create([
            'admin_id' => $adminId,
            'username' => $username,
            'action' => 'login',
            'status' => $success ? 1 : 0,
            'message' => $message ? Str::limit($message, 255, '') : null,
            'ip' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
            'created_at' => now(),
        ]);
    }

    public function recordLogout(
        string $username,
        int $adminId,
        ?string $ip = null,
        ?string $userAgent = null,
    ): void {
        LoginLog::query()->create([
            'admin_id' => $adminId,
            'username' => $username,
            'action' => 'logout',
            'status' => 1,
            'message' => '退出登录',
            'ip' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
            'created_at' => now(),
        ]);
    }
}
