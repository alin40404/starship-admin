<?php

namespace Modules\Shop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Shop\App\Models\Product;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where('name', 'like', '%'.$keyword.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $query->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Product::query()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'cover' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        $product = Product::query()->create($data);

        return ApiResponse::success($product, __('shop::messages.product_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'cover' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        $product->update($data);

        return ApiResponse::success($product->fresh(), __('shop::messages.product_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ]);

        $product->update(['status' => (int) $data['status']]);

        return ApiResponse::success($product->fresh(), __('shop::messages.product_status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        Product::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('shop::messages.product_deleted'));
    }
}
