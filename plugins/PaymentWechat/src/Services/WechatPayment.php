<?php

namespace Plugins\PaymentWechat\Services;

use Illuminate\Support\Str;
use RuntimeException;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Plugin\PluginConfigRepository;

/**
 * 微信支付官方样例：沙箱模式本地模拟；生产模式校验凭证并返回标准结构（网关对接可后续扩展）。
 */
final class WechatPayment implements PaymentInterface
{
    public function __construct(
        protected PluginConfigRepository $pluginConfigRepository,
    ) {}

    public function createOrder(array $data): array
    {
        $this->assertConfigured();

        $orderNo = trim((string) ($data['order_no'] ?? ''));
        if ($orderNo === '') {
            $orderNo = 'WX'.now()->format('YmdHis').Str::upper(Str::random(8));
        }

        $amount = max(1, (int) ($data['amount'] ?? 1));
        $subject = trim((string) ($data['subject'] ?? __('payment-wechat::messages.sandbox_order_subject')));

        if ($this->isSandbox()) {
            return [
                'provider' => 'wechat',
                'mode' => 'sandbox',
                'order_no' => $orderNo,
                'amount' => $amount,
                'subject' => $subject,
                'status' => 'created',
                'prepay_id' => 'sandbox_prepay_'.Str::lower(Str::random(12)),
            ];
        }

        return [
            'provider' => 'wechat',
            'mode' => 'live',
            'order_no' => $orderNo,
            'amount' => $amount,
            'subject' => $subject,
            'status' => 'created',
            'message' => 'live gateway integration pending',
        ];
    }

    public function pay(string $orderNo): array
    {
        $this->assertConfigured();
        $orderNo = trim($orderNo);
        if ($orderNo === '') {
            throw new RuntimeException(__('payment-wechat::messages.test_failed'));
        }

        if ($this->isSandbox()) {
            return [
                'provider' => 'wechat',
                'mode' => 'sandbox',
                'order_no' => $orderNo,
                'status' => 'payable',
                'pay_params' => [
                    'appId' => $this->config('app_id'),
                    'timeStamp' => (string) now()->timestamp,
                    'nonceStr' => Str::lower(Str::random(16)),
                    'package' => 'prepay_id=sandbox_prepay_mock',
                    'signType' => 'RSA',
                    'paySign' => 'sandbox_sign',
                ],
            ];
        }

        return [
            'provider' => 'wechat',
            'mode' => 'live',
            'order_no' => $orderNo,
            'status' => 'pending',
            'message' => 'live gateway integration pending',
        ];
    }

    public function refund(string $orderNo): array
    {
        $this->assertConfigured();
        $orderNo = trim($orderNo);
        if ($orderNo === '') {
            throw new RuntimeException(__('payment-wechat::messages.test_failed'));
        }

        return [
            'provider' => 'wechat',
            'mode' => $this->isSandbox() ? 'sandbox' : 'live',
            'order_no' => $orderNo,
            'status' => $this->isSandbox() ? 'refunded' : 'pending',
        ];
    }

    public function query(string $orderNo): array
    {
        $this->assertConfigured();
        $orderNo = trim($orderNo);
        if ($orderNo === '') {
            throw new RuntimeException(__('payment-wechat::messages.test_failed'));
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
