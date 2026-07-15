<?php

return [
    [
        'key' => 'access_key',
        'label' => __('sms-aliyun::messages.fields.access_key_label'),
        'type' => 'string',
        'rules' => ['required'],
        'group' => 'credentials',
    ],
    [
        'key' => 'secret_key',
        'label' => __('sms-aliyun::messages.fields.secret_key_label'),
        'type' => 'password',
        'encrypted' => true,
        'rules' => ['required'],
        'group' => 'credentials',
    ],
    [
        'key' => 'sign_name',
        'label' => __('sms-aliyun::messages.fields.sign_name_label'),
        'type' => 'string',
        'rules' => ['required'],
        'group' => 'template',
    ],
    [
        'key' => 'template_code',
        'label' => __('sms-aliyun::messages.fields.template_code_label'),
        'type' => 'string',
        'rules' => ['required'],
        'help' => __('sms-aliyun::messages.fields.template_code_help'),
        'group' => 'template',
    ],
];
