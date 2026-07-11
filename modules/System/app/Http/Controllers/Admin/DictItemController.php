<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;

class DictItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'dict_type_id' => ['required', 'integer', Rule::exists(DictType::class, 'id')],
        ]);

        $query = DictItem::query()
            ->where('dict_type_id', (int) $request->get('dict_type_id'))
            ->orderBy('sort')
            ->orderBy('id');

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dict_type_id' => ['required', 'integer', Rule::exists(DictType::class, 'id')],
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $item = DictItem::query()->create($data);

        return ApiResponse::success($item, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = DictItem::query()->findOrFail($id);
        $data = $request->validate([
            'dict_type_id' => ['sometimes', 'integer', Rule::exists(DictType::class, 'id')],
            'label' => ['sometimes', 'string', 'max:100'],
            'value' => ['sometimes', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $item->update($data);

        return ApiResponse::success($item->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        DictItem::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, '已删除');
    }
}
