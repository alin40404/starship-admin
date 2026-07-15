<?php

return [
    'host' => env('PLUGIN_MAIL_SMTP_HOST', ''),
    'port' => env('PLUGIN_MAIL_SMTP_PORT', '465'),
    'username' => env('PLUGIN_MAIL_SMTP_USERNAME', ''),
    'password' => env('PLUGIN_MAIL_SMTP_PASSWORD', ''),
    'encryption' => env('PLUGIN_MAIL_SMTP_ENCRYPTION', 'ssl'),
    'from_address' => env('PLUGIN_MAIL_SMTP_FROM_ADDRESS', ''),
    'from_name' => env('PLUGIN_MAIL_SMTP_FROM_NAME', ''),
    // 以下由「高级」Tab 管理，不进 config.schema
    'reply_to' => env('PLUGIN_MAIL_SMTP_REPLY_TO', ''),
    'timeout' => env('PLUGIN_MAIL_SMTP_TIMEOUT', '30'),
    'verify_peer' => env('PLUGIN_MAIL_SMTP_VERIFY_PEER', true),
    'recipients_json' => '[]',
];
