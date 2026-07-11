<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\DictType;
use Modules\System\App\Support\AdminRuntimeConfig;

class DictTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DictType::query()->withCount('items');

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('code', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->get('name').'%');
        }
        if ($request->filled('code')) {
            $query->where('code', 'like', '%'.$request->get('code').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $sortField = $request->get('sort_field');
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = [
            'id' => 'id',
            'name' => 'name',
            'code' => 'code',
            'status' => 'status',
            'updated_at' => 'updated_at',
        ];

        if (is_string($sortField) && isset($allowedSorts[$sortField])) {
            $query->orderBy($allowedSorts[$sortField], $sortOrder)->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', Rule::unique(DictType::class, 'code')],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $type = DictType::query()->create($data);

        return ApiResponse::success($type, __('system::messages.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = DictType::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => ['sometimes', 'string', 'max:100', Rule::unique(DictType::class, 'code')->ignore($type->id)],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $type->update($data);

        return ApiResponse::success($type->fresh(), __('system::messages.updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        DictType::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('system::messages.deleted'));
    }
}
