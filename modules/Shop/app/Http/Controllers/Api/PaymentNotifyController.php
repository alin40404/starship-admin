<?php

namespace Modules\Shop\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shop\App\Services\OrderService;
use Starship\Core\Support\ApiResponse;
use Throwable;

/**
 * 支付渠道异步回调（无需后台登录）。
 */
class PaymentNotifyController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_no' => ['required', 'string', 'max:32'],
            'status' => ['sometimes', 'string', 'max:32'],
            'trade_state' => ['sometimes', 'string', 'max:32'],
            'trade_no' => ['sometimes', 'nullable', 'string', 'max:64'],
            'provider' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        try {
            $order = $this->orderService->handleNotify(
                (string) $data['order_no'],
                $data,
            );

            return ApiResponse::success([
                'order_no' => $order->order_no,
                'status' => $order->status,
            ], __('shop::messages.notify_ok'));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
