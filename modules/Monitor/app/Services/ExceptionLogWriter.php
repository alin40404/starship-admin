<?php

namespace Modules\Monitor\App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Monitor\App\Models\ExceptionLog;
use Modules\System\App\Models\Admin;
use Throwable;

class ExceptionLogWriter
{
    private const RATE_LIMIT = 10;

    private const RATE_WINDOW_SECONDS = 60;

    public function record(Throwable $exception): void
    {
        if (! Exceptions::shouldReport($exception)) {
            return;
        }

        $request = request();
        $traceId = (string) Str::uuid();
        $exceptionClass = $exception::class;
        $file = $exception->getFile();
        $line = $exception->getLine();
        $message = Str::limit($exception->getMessage(), 500, '');

        if ($this->isRateLimited($exceptionClass, $file, $line)) {
            return;
        }

        $admin = $request?->user();
        $adminId = $admin instanceof Admin ? $admin->id : null;
        $adminName = $admin instanceof Admin ? ($admin->username ?: $admin->nickname) : null;

        Log::error('[monitor:'.$traceId.'] '.$message, [
            'trace_id' => $traceId,
            'exception' => $exceptionClass,
            'file' => $file,
            'line' => $line,
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'admin_id' => $adminId,
            'trace' => $exception->getTraceAsString(),
        ]);

        ExceptionLog::query()->create([
            'trace_id' => $traceId,
            'exception_class' => Str::limit($exceptionClass, 191, ''),
            'message' => $message,
            'file' => Str::limit($file, 500, ''),
            'line' => $line,
            'request_method' => $request?->method(),
            'request_url' => $request ? Str::limit($request->fullUrl(), 500, '') : null,
            'admin_id' => $adminId,
            'admin_name' => $adminName ? Str::limit((string) $adminName, 100, '') : null,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? Str::limit((string) $request->userAgent(), 500, '') : null,
            'created_at' => now(),
        ]);
    }

    private function isRateLimited(string $exceptionClass, string $file, int $line): bool
    {
        $key = 'monitor:exception:'.md5($exceptionClass.'|'.$file.'|'.$line);
        $count = (int) Cache::get($key, 0);

        if ($count >= self::RATE_LIMIT) {
            return true;
        }

        Cache::put($key, $count + 1, now()->addSeconds(self::RATE_WINDOW_SECONDS));

        return false;
    }
}
