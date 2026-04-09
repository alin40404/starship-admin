<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\DictType;

class DictTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DictType::query()->orderByDesc('id');

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
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

        return ApiResponse::success($type, '创建成功');
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

        return ApiResponse::success($type->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        DictType::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }
}
