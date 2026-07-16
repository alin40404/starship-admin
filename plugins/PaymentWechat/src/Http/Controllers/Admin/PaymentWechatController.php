<?php

namespace Plugins\PaymentWechat\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Support\ApiResponse;

class PaymentWechatController extends Controller
{
    public function test(PaymentInterface $payment): JsonResponse
    {
        $orderNo = 'WXTEST'.now()->format('YmdHis').Str::lower(Str::random(6));

        try {
            $prepay = $payment->prepay($orderNo, [
                'subject' => __('payment-wechat::messages.sandbox_order_subject'),
                'amount' => 1,
            ]);
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
            'order_no' => $orderNo,
            'prepay' => $prepay,
            'queried' => $queried,
        ], __('payment-wechat::messages.test_success'));
    }
}
