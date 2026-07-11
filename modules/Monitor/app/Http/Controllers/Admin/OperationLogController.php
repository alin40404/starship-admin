<?php

namespace Modules\Monitor\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Monitor\App\Models\OperationLog;

class OperationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OperationLog::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('admin_name', 'like', '%'.$keyword.'%')
                    ->orWhere('action', 'like', '%'.$keyword.'%')
                    ->orWhere('url', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('module')) {
            $query->where('module', (string) $request->get('module'));
        }
        if ($request->filled('method')) {
            $query->where('method', strtoupper((string) $request->get('method')));
        }
        if ($request->filled('admin_name')) {
            $query->where('admin_name', 'like', '%'.$request->get('admin_name').'%');
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to').' 23:59:59');
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id' => 'id', 'created_at' => 'created_at', 'module' => 'module'];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $log = OperationLog::query()->findOrFail($id);
        $data = $log->toArray();
        if (is_string($data['content'] ?? null)) {
            $decoded = json_decode($data['content'], true);
            $data['content'] = is_array($decoded) ? $decoded : $data['content'];
        }

        return ApiResponse::success($data);
    }

    public function destroy(int $id): JsonResponse
    {
        OperationLog::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删�?);
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = OperationLog::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], '已批量删�?);
    }
}
