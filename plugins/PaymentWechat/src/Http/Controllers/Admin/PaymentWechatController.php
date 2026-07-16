<?php

namespace Plugins\PaymentWechat\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Support\ApiResponse;

class PaymentWechatController extends Controller
{
    public function test(PaymentInterface $payment): JsonResponse
    {
        try {
            $created = $payment->createOrder([
                'subject' => __('payment-wechat::messages.sandbox_order_subject'),
                'amount' => 1,
            ]);
            $orderNo = (string) ($created['order_no'] ?? '');
            $queried = $payment->query($orderNo);
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('payment-wechat::messages.test_failed'),
                422,
            );
        }

        return ApiResponse::success([
            'created' => $created,
            'queried' => $queried,
        ], __('payment-wechat::messages.test_success'));
    }
}
