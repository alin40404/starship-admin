<?php

namespace Modules\Shop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Shop\App\Models\Brand;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Brand::query();

        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%'.(string) $request->get('keyword').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $query->orderByDesc('sort')->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function options(): JsonResponse
    {
        $items = Brand::query()
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->get(['id', 'name']);

        return ApiResponse::success($items);
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Brand::query()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'logo' => ['nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'integer', Rule::in([0, 1])],
        ]);

        $brand = Brand::query()->create($data);

        return ApiResponse::success($brand, __('shop::messages.brand_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = Brand::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'logo' => ['nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'integer', Rule::in([0, 1])],
        ]);

        $brand->update($data);

        return ApiResponse::success($brand->fresh(), __('shop::messages.brand_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Brand::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('shop::messages.brand_deleted'));
    }
}
