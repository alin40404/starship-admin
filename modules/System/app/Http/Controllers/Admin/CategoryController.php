<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\System\App\Models\Category;
use Modules\System\App\Support\CategoryType;

class CategoryController extends Controller
{
    private const FLAG_OPTIONS = ['hot', 'index', 'recommend'];

    public function types(): JsonResponse
    {
        return ApiResponse::success(CategoryType::options());
    }

    /**
     * 分类类型选择器：需登录，无按钮权限。
     */
    public function optionsTypes(): JsonResponse
    {
        return $this->types();
    }

    /**
     * 分类选择器树：需登录，无按钮权限；仅返回启用（normal）节点。
     */
    public function optionsTree(Request $request): JsonResponse
    {
        $query = Category::query()
            ->where('status', 'normal')
            ->orderByDesc('sort')
            ->orderByDesc('id');

        $type = $request->get('type');
        if (is_string($type) && $type !== '' && $type !== 'all') {
            $query->where('type', $type);
        }

        $rows = $query->get(['id', 'parent_id', 'type', 'name', 'nickname', 'status']);

        return ApiResponse::success($this->buildOptionsTree($rows));
    }

    public function tree(Request $request): JsonResponse
    {
        $query = Category::query()->orderByDesc('sort')->orderByDesc('id');

        $type = $request->get('type');
        if (is_string($type) && $type !== '' && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($request->filled('search')) {
            $search = (string) $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nickname', 'like', '%'.$search.'%');
            });
        }

        $rows = $query->get();

        return ApiResponse::success($this->buildTree($rows));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success($this->formatCategory(Category::query()->findOrFail($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        $category = Category::query()->create($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('system::messages.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $data = $this->validatePayload($request, true);

        if (isset($data['parent_id']) && (int) $data['parent_id'] !== (int) $category->parent_id) {
            $parentId = (int) $data['parent_id'];
            if ($parentId === (int) $category->id) {
                return ApiResponse::error(__('system::messages.category_parent_self'), 422);
            }
            if ($parentId > 0 && in_array($parentId, $this->descendantIds($category->id), true)) {
                return ApiResponse::error(__('system::messages.category_parent_invalid'), 422);
            }
        }

        $category->update($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('system::messages.updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['normal', 'hidden'])],
        ]);

        $category->update($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('system::messages.status_updated'));
    }

    public function updateSort(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $data = $request->validate([
            'sort' => ['required', 'integer', 'min:0'],
        ]);

        $category->update($data);

        return ApiResponse::success($this->formatCategory($category->fresh()), __('system::messages.sort_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::query()->whereKey($id)->firstOrFail();

        if (Category::query()->where('parent_id', $category->id)->exists()) {
            return ApiResponse::error(__('system::messages.category_has_children'), 422);
        }

        $category->delete();

        return ApiResponse::success(null, __('system::messages.deleted'));
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deletableIds = [];
        foreach ($ids as $id) {
            if (Category::query()->where('parent_id', $id)->exists()) {
                continue;
            }
            $deletableIds[] = (int) $id;
        }

        if ($deletableIds === []) {
            return ApiResponse::error(__('system::messages.category_batch_has_children'), 422);
        }

        $deleted = Category::query()->whereIn('id', $deletableIds)->delete();
        $skipped = count($ids) - count($deletableIds);

        return ApiResponse::success(
            ['deleted' => $deleted, 'skipped' => $skipped],
            $skipped > 0 ? __('system::messages.batch_deleted_partial_children', ['deleted' => $deleted, 'skipped' => $skipped]) : __('system::messages.batch_deleted'),
        );
    }

    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'string', Rule::in(['normal', 'hidden'])],
        ]);

        $updated = Category::query()
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return ApiResponse::success(['updated' => $updated], __('system::messages.category_batch_status_updated'));
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
                'nickname' => (string) $item->nickname,
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
            $children = Category::query()->where('parent_id', $current)->pluck('id')->all();
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
        $typeLabels = CategoryType::labelMap();
        $flags = $this->normalizeFlags($category->flag);

        return [
            'id' => $category->id,
            'parent_id' => (int) $category->parent_id,
            'type' => (string) $category->type,
            'type_label' => $typeLabels[$category->type] ?? $category->type,
            'name' => (string) $category->name,
            'nickname' => (string) $category->nickname,
            'flag' => $flags,
            'flag_labels' => $this->flagLabels($flags),
            'image' => $category->image,
            'keywords' => $category->keywords,
            'description' => $category->description,
            'diyname' => $category->diyname,
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
            'type' => ['sometimes', 'string', 'max:30'],
            'name' => ['sometimes', 'string', 'max:30'],
            'nickname' => ['sometimes', 'string', 'max:50'],
            'flag' => ['nullable', 'array'],
            'flag.*' => ['string', Rule::in(self::FLAG_OPTIONS)],
            'image' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'diyname' => ['nullable', 'string', 'max:30'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['normal', 'hidden'])],
        ];

        if (! $partial) {
            $rules['type'] = ['required', 'string', 'max:30'];
            $rules['name'] = ['required', 'string', 'max:30'];
            $rules['nickname'] = ['required', 'string', 'max:50'];
        }

        $data = $request->validate($rules);

        if (array_key_exists('type', $data) && ! CategoryType::isValid($data['type'])) {
            abort(422, '无效的栏目类型，请先在字典管理维护 category_type');
        }

        if (array_key_exists('flag', $data)) {
            $data['flag'] = $this->encodeFlags($data['flag']);
        }

        if (! array_key_exists('parent_id', $data)) {
            $data['parent_id'] = 0;
        }

        return $data;
    }

    /**
     * @param  list<string>|null  $flags
     */
    private function encodeFlags(?array $flags): ?string
    {
        if ($flags === null || $flags === []) {
            return null;
        }

        $flags = array_values(array_intersect($flags, self::FLAG_OPTIONS));

        return $flags === [] ? null : implode(',', $flags);
    }

    /**
     * @return list<string>
     */
    private function normalizeFlags(?string $flag): array
    {
        if ($flag === null || $flag === '') {
            return [];
        }

        return array_values(array_intersect(explode(',', $flag), self::FLAG_OPTIONS));
    }

    /**
     * @param  list<string>  $flags
     * @return list<string>
     */
    private function flagLabels(array $flags): array
    {
        $map = [
            'hot' => '热门',
            'index' => '首页',
            'recommend' => '推荐',
        ];

        return array_values(array_map(fn ($flag) => $map[$flag] ?? $flag, $flags));
    }
}
