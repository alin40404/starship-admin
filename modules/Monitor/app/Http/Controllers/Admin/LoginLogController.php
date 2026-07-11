<?php

namespace Modules\Monitor\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Monitor\App\Models\LoginLog;

class LoginLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LoginLog::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('username', 'like', '%'.$keyword.'%')
                    ->orWhere('message', 'like', '%'.$keyword.'%')
                    ->orWhere('ip', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('username')) {
            $query->where('username', 'like', '%'.$request->get('username').'%');
        }
        if ($request->filled('action')) {
            $query->where('action', (string) $request->get('action'));
        }
        if ($request->has('status') && $request->get('status') !== '' && $request->get('status') !== null) {
            $query->where('status', (int) $request->get('status'));
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to').' 23:59:59');
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id' => 'id', 'created_at' => 'created_at', 'username' => 'username'];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(LoginLog::query()->findOrFail($id));
    }

    public function destroy(int $id): JsonResponse
    {
        LoginLog::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('monitor::messages.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = LoginLog::query()->whereIn('id', $ids)->delete();

        return ApiResponse::success(['deleted' => $deleted], __('monitor::messages.batch_deleted'));
    }
}
