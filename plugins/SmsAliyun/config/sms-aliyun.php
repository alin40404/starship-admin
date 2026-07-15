<?php

return [
    'access_key' => env('PLUGIN_SMS_ALIYUN_ACCESS_KEY', ''),
    'secret_key' => env('PLUGIN_SMS_ALIYUN_SECRET_KEY', ''),
    'sign_name' => env('PLUGIN_SMS_ALIYUN_SIGN_NAME', ''),
    'template_code' => env('PLUGIN_SMS_ALIYUN_TEMPLATE_CODE', ''),
    // 本地维护的模板清单（JSON）；不进 config.schema，由「模板」Tab 管理
    'templates_json' => '[]',
];
