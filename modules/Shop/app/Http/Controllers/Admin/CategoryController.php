<?php

namespace Modules\Shop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Shop\App\Support\ShopCategoryType;
use Modules\System\App\Models\Category;
use Starship\Core\Support\ApiResponse;

/**
 * Shop 商品分类：共用系统 categories 表，固定 type=product，独立 shop.categories.* 权限。
 */
class CategoryController extends Controller
{
    /**
     * 栏目选择器：需登录，无按钮权限；仅返回启用节点。
     */
    public function optionsTree(): JsonResponse
    {
        $rows = $this->scopedQuery()
            ->where('status', 'normal')
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->get(['id', 'parent_id', 'type', 'name', 'slug', 'status']);

        return ApiResponse::success($this->buildOptionsTree($rows));
    }

    public function tree(Request $request): JsonResponse
    {
        $query = $this->scopedQuery()->orderByDesc('sort')->orderByDesc('id');

        if ($request->filled('search')) {
            $search = (string) $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        return ApiResponse::success($this->buildTree($query->get()));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success($this->formatCategory($this->findScopedOrFail($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['type'] = ShopCategoryType::PRODUCT;

        $category = Category::query()->create($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('shop::messages.category_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = $this->findScopedOrFail($id);
        $data = $this->validatePayload($request, true);
        $data['type'] = ShopCategoryType::PRODUCT;

        if (isset($data['parent_id']) && (int) $data['parent_id'] !== (int) $category->parent_id) {
            $parentId = (int) $data['parent_id'];
            if ($parentId === (int) $category->id) {
                return ApiResponse::error(__('system::messages.category_parent_self'), 422);
            }
            if ($parentId > 0 && in_array($parentId, $this->descendantIds($category->id), true)) {
                return ApiResponse::error(__('system::messages.category_parent_invalid'), 422);
            }
            if ($parentId > 0) {
                $this->findScopedOrFail($parentId);
            }
        }

        $category->update($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('shop::messages.category_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $category = $this->findScopedOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['normal', 'hidden'])],
        ]);

        $category->update($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('shop::messages.category_status_updated'));
    }

    public function updateSort(Request $request, int $id): JsonResponse
    {
        $category = $this->findScopedOrFail($id);
        $data = $request->validate([
            'sort' => ['required', 'integer', 'min:0'],
        ]);

        $category->update($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('shop::messages.category_sort_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = $this->findScopedOrFail($id);

        if ($this->scopedQuery()->where('parent_id', $category->id)->exists()) {
            return ApiResponse::error(__('system::messages.category_has_children'), 422);
        }

        $category->delete();

        return ApiResponse::success(null, __('shop::messages.category_deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $scopedIds = $this->scopedQuery()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $deletableIds = [];
        foreach ($scopedIds as $id) {
            if ($this->scopedQuery()->where('parent_id', $id)->exists()) {
                continue;
            }
            $deletableIds[] = $id;
        }

        if ($deletableIds === []) {
            return ApiResponse::error(__('system::messages.category_batch_has_children'), 422);
        }

        $deleted = $this->scopedQuery()->whereIn('id', $deletableIds)->delete();
        $skipped = count($ids) - count($deletableIds);

        return ApiResponse::success(
            ['deleted' => $deleted, 'skipped' => $skipped],
            $skipped > 0
                ? __('system::messages.batch_deleted_partial_children', ['deleted' => $deleted, 'skipped' => $skipped])
                : __('system::messages.batch_deleted'),
        );
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'string', Rule::in(['normal', 'hidden'])],
        ]);

        $updated = $this->scopedQuery()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], __('shop::messages.category_batch_status_updated'));
    }

    /**
     * @return Builder<Category>
     */
    private function scopedQuery(): Builder
    {
        return Category::query()->where('type', ShopCategoryType::PRODUCT);
    }

    private function findScopedOrFail(int $id): Category
    {
        return $this->scopedQuery()->whereKey($id)->firstOrFail();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $rows
     * @return array<int, mixed>
     */
    private function buildOptionsTree($rows, int $parentId = 0): array
    {
        $branch = [];

        foreach ($rows as $item) {
            if ((int) $item->parent_id !== $parentId) {
                continue;
            }

            $node = [
                'id' => $item->id,
                'parent_id' => (int) $item->parent_id,
                'type' => (string) $item->type,
                'name' => (string) $item->name,
                'slug' => (string) $item->slug,
                'status' => (string) $item->status,
            ];
            $children = $this->buildOptionsTree($rows, (int) $item->id);
            if ($children !== []) {
                $node['children'] = $children;
            }

            $branch[] = $node;
        }

        return $branch;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $rows
     * @return array<int, mixed>
     */
    private function buildTree($rows, int $parentId = 0): array
    {
        $branch = [];

        foreach ($rows as $item) {
            if ((int) $item->parent_id !== $parentId) {
                continue;
            }

            $formatted = $this->formatCategory($item);
            $children = $this->buildTree($rows, (int) $item->id);
            if ($children !== []) {
                $formatted['children'] = $children;
            }

            $branch[] = $formatted;
        }

        return $branch;
    }

    /**
     * @return array<int, int>
     */
    private function descendantIds(int $id): array
    {
        $ids = [];
        $stack = [$id];

        while ($stack !== []) {
            $current = array_pop($stack);
            $children = $this->scopedQuery()->where('parent_id', $current)->pluck('id')->all();
            foreach ($children as $childId) {
                $ids[] = (int) $childId;
                $stack[] = (int) $childId;
            }
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'parent_id' => (int) $category->parent_id,
            'type' => (string) $category->type,
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'image' => $category->image,
            'keywords' => $category->keywords,
            'description' => $category->description,
            'sort' => (int) $category->sort,
            'status' => (string) $category->status,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'name' => ['sometimes', 'string', 'max:30'],
            'slug' => ['sometimes', 'string', 'max:50'],
            'image' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['normal', 'hidden'])],
        ];

        if (! $partial) {
            $rules['name'] = ['required', 'string', 'max:30'];
            $rules['slug'] = ['required', 'string', 'max:50'];
        }

        $data = $request->validate($rules);

        if (! array_key_exists('parent_id', $data)) {
            $data['parent_id'] = 0;
        }

        if (! empty($data['parent_id'])) {
            $this->findScopedOrFail((int) $data['parent_id']);
        }

        return $data;
    }
}
