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
            'title' => $this->resolveTitle($request->method(), $module, $resource),
            'content' => $this->writer->sanitizePayload($request->all()),
            'method' => strtoupper($request->method()),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function resolveTitle(string $method, ?string $module, string $resource): string
    {
        $methodLabel = match (strtoupper($method)) {
            'POST' => '新增',
            'PUT', 'PATCH' => '更新',
            'DELETE' => '删除',
            default => '操作',
        };

        return sprintf('%s %s/%s', $methodLabel, $module ?? 'system', $resource);
    }
}
