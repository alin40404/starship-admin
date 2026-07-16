<?php

namespace Modules\Shop\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Services\OrderService;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::query();

        if ($request->filled('keyword')) {
            $keyword = (string) $request->get('keyword');
            $query->where(function ($q) use ($keyword): void {
                $q->where('order_no', 'like', '%'.$keyword.'%')
                    ->orWhere('product_name', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->get('status'));
        }
        if ($request->filled('member_id')) {
            $query->where('member_id', (int) $request->get('member_id'));
        }

        $query->orderByDesc('id');

        return ApiResponse::success($query->paginate(AdminRuntimeConfig::perPage($request->get('per_page'))));
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(Order::query()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
            'member_id' => ['nullable', 'integer', 'min:1'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $order = $this->orderService->createFromProduct(
                (int) $data['product_id'],
                (int) ($data['quantity'] ?? 1),
                isset($data['member_id']) ? (int) $data['member_id'] : null,
                $data['remark'] ?? null,
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($order, __('shop::messages.order_created'));
    }

    public function prepay(int $id): JsonResponse
    {
        $order = Order::query()->findOrFail($id);

        try {
            $result = $this->orderService->prepay($order);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($result, __('shop::messages.order_prepay_ok'));
    }

    public function syncStatus(int $id): JsonResponse
    {
        $order = Order::query()->findOrFail($id);

        try {
            $order = $this->orderService->syncPaymentStatus($order);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($order, __('shop::messages.order_synced'));
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $order = Order::query()->findOrFail($id);
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->orderService->refund($order, $data['reason'] ?? null);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($result, __('shop::messages.order_refunded'));
    }

    public function cancel(int $id): JsonResponse
    {
        $order = Order::query()->findOrFail($id);

        try {
            $order = $this->orderService->cancel($order);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($order, __('shop::messages.order_cancelled'));
    }

    public function destroy(int $id): JsonResponse
    {
        $order = Order::query()->findOrFail($id);
        if (! $order->isPending()) {
            return ApiResponse::error(__('shop::messages.order_delete_forbidden'), 422);
        }

        $order->delete();

        return ApiResponse::success(null, __('shop::messages.order_deleted'));
    }
}
