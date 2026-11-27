<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\ExceptionLog;
use Modules\System\App\Support\AdminRuntimeConfig;
use Modules\System\App\Support\ApplicationLogPath;
use Starship\Core\Support\ApiResponse;

class ExceptionLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ExceptionLog::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('message', 'like', '%'.$keyword.'%')
                    ->orWhere('exception_class', 'like', '%'.$keyword.'%')
                    ->orWhere('request_url', 'like', '%'.$keyword.'%')
                    ->orWhere('trace_id', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('exception_class')) {
            $query->where('exception_class', 'like', '%'.$request->get('exception_class').'%');
        }
        if ($request->filled('is_read') && in_array((string) $request->get('is_read'), ['0', '1'], true)) {
            $query->where('is_read', (int) $request->get('is_read'));
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to').' 23:59:59');
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id' => 'id', 'created_at' => 'created_at', 'is_read' => 'is_read'];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        $log = ExceptionLog::query()->findOrFail($id);

        if ((int) $log->is_read !== 1) {
            $log->forceFill(['is_read' => 1])->save();
            $log->refresh();
        }

        return ApiResponse::success([
            ...$log->toArray(),
            'log_hint' => __('system::monitor.log_hint', [
                'log_path' => $this->resolveLogPath($log),
                'trace_id' => $log->trace_id,
            ]),
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = ExceptionLog::query()->where('is_read', 0)->count();

        return ApiResponse::success(['count' => $count]);
    }

    public function batchUpdateRead(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'is_read' => ['required', 'integer', 'in:0,1'],
        ]);

        $updated = ExceptionLog::query()
            ->whereIn('id', $payload['ids'])
            ->update(['is_read' => (int) $payload['is_read']]);

        return ApiResponse::success(
            ['updated' => $updated],
            (int) $payload['is_read'] === 1
                ? __('system::monitor.batch_marked_read')
                : __('system::monitor.batch_marked_unread'),
        );
    }

    public function markAllRead(): JsonResponse
    {
        ExceptionLog::query()
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return ApiResponse::success(null, __('system::monitor.all_marked_read'));
    }

    public function destroy(int $id): JsonResponse
    {
        ExceptionLog::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('system::monitor.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = ExceptionLog::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], __('system::monitor.batch_deleted'));
    }

    private function resolveLogPath(ExceptionLog $log): string
    {
        $stored = trim((string) ($log->log_path ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        return ApplicationLogPath::displayHint();
    }
}
