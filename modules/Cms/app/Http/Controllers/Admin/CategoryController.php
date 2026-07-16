<?php

namespace Modules\Cms\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Cms\App\Models\Category;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('slug', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $query->orderBy('sort')->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function options(): JsonResponse
    {
        $items = Category::query()
            ->where('status', 1)
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get(['id', 'name', 'parent_id']);

        return ApiResponse::success($items);
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Category::query()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', Rule::unique(Category::class, 'slug')],
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        $category = Category::query()->create($data);

        return ApiResponse::success($category, __('cms::messages.category_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'slug' => ['sometimes', 'string', 'max:80', Rule::unique(Category::class, 'slug')->ignore($category->id)],
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        $category->update($data);

        return ApiResponse::success($category->fresh(), __('cms::messages.category_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ]);

        $category->update(['status' => (int) $data['status']]);

        return ApiResponse::success($category->fresh(), __('cms::messages.category_status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Category::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('cms::messages.category_deleted'));
    }
}
