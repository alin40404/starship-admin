<?php

return [
    [
        'key' => 'app_id',
        'label' => __('payment-wechat::messages.fields.app_id_label'),
        'type' => 'string',
        'rules' => ['required'],
        'group' => 'credentials',
    ],
    [
        'key' => 'mch_id',
        'label' => __('payment-wechat::messages.fields.mch_id_label'),
        'type' => 'string',
        'rules' => ['required'],
        'group' => 'credentials',
    ],
    [
        'key' => 'api_v3_key',
        'label' => __('payment-wechat::messages.fields.api_v3_key_label'),
        'type' => 'password',
        'encrypted' => true,
        'rules' => ['required'],
        'group' => 'credentials',
    ],
    [
        'key' => 'serial_no',
        'label' => __('payment-wechat::messages.fields.serial_no_label'),
        'type' => 'string',
        'help' => __('payment-wechat::messages.fields.serial_no_help'),
        'group' => 'credentials',
    ],
    [
        'key' => 'notify_url',
        'label' => __('payment-wechat::messages.fields.notify_url_label'),
        'type' => 'string',
        'help' => __('payment-wechat::messages.fields.notify_url_help'),
        'group' => 'callback',
    ],
    [
        'key' => 'sandbox',
        'label' => __('payment-wechat::messages.fields.sandbox_label'),
        'type' => 'switch',
        'default' => '1',
        'help' => __('payment-wechat::messages.fields.sandbox_help'),
        'group' => 'callback',
    ],
];
