<?php

namespace Modules\Monitor\App\Services;

use Illuminate\Support\Str;
use Modules\Monitor\App\Models\OperationLog;
use Modules\System\App\Models\Admin;

class OperationLogWriter
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(array $payload): void
    {
        OperationLog::query()->create([
            'admin_id' => $payload['admin_id'] ?? null,
            'admin_name' => $payload['admin_name'] ?? null,
            'module' => $payload['module'] ?? null,
            'action' => (string) ($payload['action'] ?? 'unknown'),
            'title' => Str::limit((string) ($payload['title'] ?? '操作记录'), 200, ''),
            'content' => isset($payload['content'])
                ? json_encode($payload['content'], JSON_UNESCAPED_UNICODE)
                : null,
            'method' => $payload['method'] ?? null,
            'url' => isset($payload['url']) ? Str::limit((string) $payload['url'], 500, '') : null,
            'status_code' => $payload['status_code'] ?? null,
            'ip' => $payload['ip'] ?? null,
            'user_agent' => isset($payload['user_agent'])
                ? Str::limit((string) $payload['user_agent'], 500, '')
                : null,
            'created_at' => now(),
        ]);
    }

    public function resolveAdminName(?Admin $admin): ?string
    {
        if (! $admin) {
            return null;
        }

        return $admin->username ?: $admin->nickname ?: null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function sanitizePayload(array $input): array
    {
        $hidden = ['password', 'old_password', 'new_password', 'token', 'access_token'];

        $walk = function (mixed $value) use (&$walk, $hidden): mixed {
            if (! is_array($value)) {
                return $value;
            }

            $result = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && in_array(strtolower($key), $hidden, true)) {
                    $result[$key] = '***';

                    continue;
                }
                $result[$key] = $walk($item);
            }

            return $result;
        };

        return $walk($input);
    }
}
