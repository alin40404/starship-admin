<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Attachment;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;
use Modules\System\App\Support\AttachmentCategory;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $this->resolveDictType();

        $query = DictItem::query()
            ->where('dict_type_id', $type->id)
            ->orderBy('sort')
            ->orderBy('id');

        if ($request->filled('label')) {
            $query->where('label', 'like', '%'.$request->get('label').'%');
        }
        if ($request->filled('value')) {
            $query->where('value', 'like', '%'.$request->get('value').'%');
        }
        if ($request->has('status') && $request->get('status') !== '' && $request->get('status') !== null) {
            $query->where('status', (int) $request->get('status'));
        }

        return ApiResponse::success($query->paginate((int) $request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $type = $this->resolveDictType();

        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'value' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique(DictItem::class, 'value')->where('dict_type_id', $type->id),
            ],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $item = DictItem::query()->create([
            ...$data,
            'dict_type_id' => $type->id,
        ]);

        return ApiResponse::success($item, '创建成功');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = $this->resolveDictType();
        $item = $this->findCategoryItem($type->id, $id);

        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:100'],
            'value' => [
                'sometimes',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique(DictItem::class, 'value')
                    ->where('dict_type_id', $type->id)
                    ->ignore($item->id),
            ],
            'sort' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['value']) && $data['value'] !== $item->value) {
            $inUse = Attachment::query()->where('category', $item->value)->exists();
            if ($inUse) {
                return ApiResponse::error('该分类下已有附件，不能修改分类标识', 422);
            }
        }

        $item->update($data);

        return ApiResponse::success($item->fresh(), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $type = $this->resolveDictType();
        $item = $this->findCategoryItem($type->id, $id);

        $inUse = Attachment::query()->where('category', $item->value)->exists();
        if ($inUse) {
            return ApiResponse::error('该分类下还有附件，无法删除', 422);
        }

        $item->delete();

        return ApiResponse::success(null, '已删除');
    }

    private function resolveDictType(): DictType
    {
        $type = DictType::query()
            ->where('code', AttachmentCategory::DICT_TYPE_CODE)
            ->where('status', 1)
            ->first();

        if (! $type) {
            abort(422, '附件分类字典未初始化，请先执行数据种子');
        }

        return $type;
    }

    private function findCategoryItem(int $dictTypeId, int $id): DictItem
    {
        return DictItem::query()
            ->where('dict_type_id', $dictTypeId)
            ->whereKey($id)
            ->firstOrFail();
    }
}
