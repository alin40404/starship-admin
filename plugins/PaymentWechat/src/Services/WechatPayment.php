<?php

namespace Plugins\PaymentWechat\Services;

use Illuminate\Support\Str;
use RuntimeException;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Plugin\PluginConfigRepository;

/**
 * 微信支付官方样例：仅实现支付通道能力；业务订单号由 Shop 等模块先创建。
 */
final class WechatPayment implements PaymentInterface
{
    public function __construct(
        protected PluginConfigRepository $pluginConfigRepository,
    ) {}

    public function prepay(string $orderNo, array $context = []): array
    {
        $this->assertConfigured();

        $orderNo = trim($orderNo);
        if ($orderNo === '') {
            throw new RuntimeException(__('payment-wechat::messages.order_no_required'));
        }

        $amount = max(1, (int) ($context['amount'] ?? 1));
        $subject = trim((string) ($context['subject'] ?? __('payment-wechat::messages.sandbox_order_subject')));

        if ($this->isSandbox()) {
            $prepayId = 'sandbox_prepay_'.Str::lower(Str::random(12));

            return [
                'provider' => 'wechat',
                'mode' => 'sandbox',
                'order_no' => $orderNo,
                'amount' => $amount,
                'subject' => $subject,
                'status' => 'prepay_created',
                'prepay_id' => $prepayId,
                'pay_params' => [
                    'appId' => $this->config('app_id'),
                    'timeStamp' => (string) now()->timestamp,
                    'nonceStr' => Str::lower(Str::random(16)),
                    'package' => 'prepay_id='.$prepayId,
                    'signType' => 'RSA',
                    'paySign' => 'sandbox_sign',
                ],
            ];
        }

        return [
            'provider' => 'wechat',
            'mode' => 'live',
            'order_no' => $orderNo,
            'amount' => $amount,
            'subject' => $subject,
            'status' => 'pending',
            'message' => 'live gateway integration pending',
        ];
    }

    public function refund(string $orderNo, array $context = []): array
    {
        $this->assertConfigured();
        $orderNo = trim($orderNo);
        if ($orderNo === '') {
            throw new RuntimeException(__('payment-wechat::messages.order_no_required'));
        }

        return [
            'provider' => 'wechat',
            'mode' => $this->isSandbox() ? 'sandbox' : 'live',
            'order_no' => $orderNo,
            'amount' => isset($context['amount']) ? (int) $context['amount'] : null,
            'status' => $this->isSandbox() ? 'refunded' : 'pending',
        ];
    }

    public function query(string $orderNo): array
    {
        $this->assertConfigured();
        $orderNo = trim($orderNo);
        if ($orderNo === '') {
            throw new RuntimeException(__('payment-wechat::messages.order_no_required'));
        }

        if ($this->isSandbox()) {
            return [
                'provider' => 'wechat',
                'mode' => 'sandbox',
                'order_no' => $orderNo,
                'status' => 'success',
                'trade_state' => 'SUCCESS',
            ];
        }

        return [
            'provider' => 'wechat',
            'mode' => 'live',
            'order_no' => $orderNo,
            'status' => 'unknown',
            'message' => 'live gateway integration pending',
        ];
    }

    protected function assertConfigured(): void
    {
        if (
            $this->config('app_id') === ''
            || $this->config('mch_id') === ''
            || $this->config('api_v3_key') === ''
        ) {
            throw new RuntimeException(__('payment-wechat::messages.config_incomplete'));
        }
    }

    protected function isSandbox(): bool
    {
        $value = $this->config('sandbox', '1');

        return $value === '1' || $value === 'true' || $value === 'on';
    }

    protected function config(string $key, string $default = ''): string
    {
        return trim((string) $this->pluginConfigRepository->get('payment-wechat', $key, $default));
    }
}
