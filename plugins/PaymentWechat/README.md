# PaymentWechat

微信支付官方样例插件：启用后覆盖 `PaymentInterface`（仅通道：prepay / refund / query）。业务订单由 Shop 等模块创建。

## 使用

```bash
php artisan starship:plugin:install payment-wechat
php artisan starship:plugin:enable payment-wechat
```

管理端配置 AppID / 商户号 / APIv3 密钥。默认**沙箱模式**可在无真实网关时完成下单与查单测试。

禁用后回落 System `PaymentGateway`（提示安装支付插件）。
