<?php

namespace Modules\Shop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Shop\App\Models\Product;
use Modules\Shop\App\Models\ProductSku;
use Modules\Shop\App\Support\ShopCategoryType;
use Modules\Shop\App\Support\ShopFulfillmentType;
use Modules\Shop\App\Support\ShopProductType;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with(['skus', 'brand']);

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where('name', 'like', '%'.$keyword.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }
        if ($request->filled('product_type')) {
            $query->where('product_type', (string) $request->get('product_type'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->get('category_id'));
        }

        $query->orderByDesc('sort')->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(
            Product::query()->with(['skus', 'brand'])->findOrFail($id),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->normalizeProductTypes($this->validateProduct($request));
        $skus = $this->validateSkus($request);

        $product = DB::transaction(function () use ($data, $skus): Product {
            $product = Product::query()->create($data);
            $this->syncSkus($product, $skus);

            return $product->fresh(['skus', 'brand']);
        });

        return ApiResponse::success($product, __('shop::messages.product_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        $data = $this->normalizeProductTypes($this->validateProduct($request, true), $product);
        $skus = $request->has('skus') ? $this->validateSkus($request) : null;

        $product = DB::transaction(function () use ($product, $data, $skus): Product {
            if ($data !== []) {
                $product->update($data);
            }
            if ($skus !== null) {
                $this->syncSkus($product, $skus);
            }

            return $product->fresh(['skus', 'brand']);
        });

        return ApiResponse::success($product, __('shop::messages.product_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $product = Product::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ]);

        $product->update(['status' => (int) $data['status']]);

        return ApiResponse::success($product->fresh(['skus']), __('shop::messages.product_status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::query()->whereKey($id)->firstOrFail();
        DB::transaction(function () use ($product): void {
            ProductSku::query()->where('product_id', $product->id)->delete();
            $product->delete();
        });

        return ApiResponse::success(null, __('shop::messages.product_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateProduct(Request $request, bool $partial = false): array
    {
        $rules = [
            'category_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('categories', 'id')->where('type', ShopCategoryType::PRODUCT),
            ],
            'brand_id' => ['nullable', 'integer', 'min:1', Rule::exists('shop_brands', 'id')],
            'product_type' => ['sometimes', 'string', Rule::in(ShopProductType::values())],
            'fulfillment_type' => ['sometimes', 'nullable', 'string', Rule::in(ShopFulfillmentType::values())],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'cover' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'extra' => ['nullable', 'array'],
        ];

        if (! $partial) {
            $rules['product_type'] = ['required', 'string', Rule::in(ShopProductType::values())];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeProductTypes(array $data, ?Product $existing = null): array
    {
        $productType = $data['product_type']
            ?? $existing?->product_type
            ?? ShopProductType::PHYSICAL;

        if (! array_key_exists('fulfillment_type', $data)
            || $data['fulfillment_type'] === null
            || $data['fulfillment_type'] === '') {
            $data['fulfillment_type'] = ShopFulfillmentType::defaultForProductType((string) $productType);
        }

        if (! ShopFulfillmentType::isAllowedForProductType((string) $data['fulfillment_type'], (string) $productType)) {
            throw ValidationException::withMessages([
                'fulfillment_type' => [__('shop::messages.fulfillment_not_allowed')],
            ]);
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function validateSkus(Request $request): array
    {
        $data = $request->validate([
            'skus' => ['nullable', 'array'],
            'skus.*.id' => ['nullable', 'integer', 'min:1'],
            'skus.*.sku_code' => ['nullable', 'string', 'max:64'],
            'skus.*.specs' => ['nullable', 'array'],
            'skus.*.price' => ['required', 'integer', 'min:0'],
            'skus.*.stock' => ['required', 'integer', 'min:0'],
            'skus.*.status' => ['sometimes', 'integer', 'in:0,1'],
            'skus.*.extra' => ['nullable', 'array'],
        ]);

        return $data['skus'] ?? [];
    }

    /**
     * @param  list<array<string, mixed>>  $skus
     */
    protected function syncSkus(Product $product, array $skus): void
    {
        if ($skus === []) {
            $skus = [[
                'price' => (int) ($product->price ?? 0),
                'stock' => (int) ($product->stock ?? 0),
                'status' => 1,
            ]];
        }

        $keptIds = [];
        $minPrice = null;
        $totalStock = 0;

        foreach ($skus as $index => $row) {
            $skuCode = trim((string) ($row['sku_code'] ?? ''));
            if ($skuCode === '') {
                $skuCode = 'SKU'.$product->id.'_'.($index + 1).'_'.Str::upper(Str::random(4));
            }

            $payload = [
                'product_id' => $product->id,
                'sku_code' => $skuCode,
                'specs' => $row['specs'] ?? null,
                'price' => (int) $row['price'],
                'stock' => (int) $row['stock'],
                'status' => (int) ($row['status'] ?? 1),
                'extra' => $row['extra'] ?? null,
            ];

            $skuId = isset($row['id']) ? (int) $row['id'] : 0;
            if ($skuId > 0) {
                $sku = ProductSku::query()
                    ->where('product_id', $product->id)
                    ->whereKey($skuId)
                    ->firstOrFail();
                $sku->update($payload);
            } else {
                $sku = ProductSku::query()->create($payload);
            }

            $keptIds[] = (int) $sku->id;
            $minPrice = $minPrice === null ? (int) $sku->price : min($minPrice, (int) $sku->price);
            $totalStock += (int) $sku->stock;
        }

        ProductSku::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', $keptIds)
            ->delete();

        $product->update([
            'price' => $minPrice ?? 0,
            'stock' => $totalStock,
        ]);
    }
}
