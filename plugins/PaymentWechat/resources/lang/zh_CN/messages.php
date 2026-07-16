<?php

return [
    'config_incomplete' => '请先完善 AppID、商户号与 APIv3 密钥',
    'test_success' => '微信支付样例连通性测试通过',
    'test_failed' => '微信支付样例连通性测试失败',
    'order_no_required' => '业务订单号不能为空',
    'sandbox_order_subject' => 'Starship 支付插件测试',
    'fields' => [
        'app_id_label' => 'AppID',
        'mch_id_label' => '商户号',
        'api_v3_key_label' => 'APIv3 密钥',
        'serial_no_label' => '证书序列号',
        'serial_no_help' => '生产环境必填；沙箱模式可留空',
        'notify_url_label' => '支付回调地址',
        'notify_url_help' => '可选；沙箱测试可不填',
        'sandbox_label' => '沙箱模式',
        'sandbox_help' => '开启后使用本地模拟下单/查单，不请求微信网关',
    ],
];
