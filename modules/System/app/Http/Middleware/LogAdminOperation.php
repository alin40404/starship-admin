<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Models\Admin;
use Modules\System\App\Services\OperationLogWriter;
use Symfony\Component\HttpFoundation\Response;

class LogAdminOperation
{
    private const SKIP_PREFIXES = [
        'admin-api/auth/',
        'admin-api/monitor/',
    ];

    /** 业务写操作仅 POST（GET/POST 约定）；仍兼容历史 PUT/PATCH/DELETE 日志写入 */
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private readonly OperationLogWriter $writer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! in_array(strtoupper($request->method()), self::WRITE_METHODS, true)) {
            return;
        }

        $path = trim($request->path(), '/').'/';
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $module = ($segments[0] ?? null) === 'admin-api' ? ($segments[1] ?? null) : null;
        $resource = $segments[2] ?? ($segments[1] ?? 'unknown');
        $actionCode = strtolower($request->method()).'.'.$resource;

        $this->writer->record([
            'admin_id' => $admin->id,
            'admin_name' => $this->writer->resolveAdminName($admin),
            'module' => $module,
            'action' => $actionCode,
            'title' => $this->resolveTitle($request->method(), $path, $module, $resource),
            'content' => $this->writer->sanitizePayload($request->all()),
            'method' => strtoupper($request->method()),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function resolveTitle(string $method, string $path, ?string $module, string $resource): string
    {
        $upper = strtoupper($method);
        $methodLabel = match (true) {
            $upper === 'DELETE'
                || str_contains($path, '/delete/')
                || str_contains($path, '/batch-destroy/')
                || str_contains($path, '/uninstall/') => '删除',
            $upper === 'PUT', $upper === 'PATCH' => '更新',
            $upper === 'POST' && $this->isLikelyCreatePath($path) => '新增',
            $upper === 'POST' => '更新',
            default => '操作',
        };

        return sprintf('%s %s/%s', $methodLabel, $module ?? 'system', $resource);
    }

    /** 集合级 POST（无 {id} 段）视为新增；带 /status|/sort|/config 等视为更新 */
    private function isLikelyCreatePath(string $path): bool
    {
        $trimmed = trim($path, '/');
        $parts = explode('/', $trimmed);
        // admin-api/{module}/{resource}[/...]
        if (($parts[0] ?? '') !== 'admin-api' || count($parts) < 3) {
            return false;
        }
        $tail = array_slice($parts, 3);
        if ($tail === []) {
            return true;
        }

        return false;
    }
}
