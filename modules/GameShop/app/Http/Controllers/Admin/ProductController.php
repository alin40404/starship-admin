<?php

namespace Modules\GameShop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\GameShop\App\Models\GameProduct;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GameProduct::query()->with('game');

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where('name', 'like', '%'.$keyword.'%');
        }
        if ($request->filled('game_id')) {
            $query->where('game_id', (int) $request->get('game_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }

        $query->orderByDesc('sort')->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(GameProduct::query()->with('game')->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateProduct($request);
        $product = GameProduct::query()->create($data);

        return ApiResponse::success($product->load('game'), __('game_shop::messages.product_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = GameProduct::query()->findOrFail($id);
        $data = $this->validateProduct($request, true);
        if ($data !== []) {
            $product->update($data);
        }

        return ApiResponse::success($product->fresh('game'), __('game_shop::messages.product_updated'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $product = GameProduct::query()->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ]);
        $product->update(['status' => (int) $data['status']]);

        return ApiResponse::success($product->fresh('game'), __('game_shop::messages.product_status_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        GameProduct::query()->whereKey($id)->firstOrFail()->delete();

        return ApiResponse::success(null, __('game_shop::messages.product_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateProduct(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'game_id' => [$partial ? 'sometimes' : 'required', 'integer', 'min:1', Rule::exists('game_shop_games', 'id')],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'sku_code' => ['nullable', 'string', 'max:64'],
            'product_kind' => ['sometimes', 'string', Rule::in(['topup', 'cdkey', 'item'])],
            'price' => ['sometimes', 'integer', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'cover' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
            'sort' => ['sometimes', 'integer', 'min:0'],
        ]);
    }
}
