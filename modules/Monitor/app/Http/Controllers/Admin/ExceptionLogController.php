<?php

namespace Modules\Monitor\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Monitor\App\Models\ExceptionLog;

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
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to').' 23:59:59');
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id' => 'id', 'created_at' => 'created_at'];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $log = ExceptionLog::query()->findOrFail($id);

        return ApiResponse::success([
            ...$log->toArray(),
            'log_hint' => '完整堆栈请查看服务器日志 storage/logs/laravel.log，搜索 trace_id='.$log->trace_id,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        ExceptionLog::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = ExceptionLog::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], '已批量删除');
    }
}
