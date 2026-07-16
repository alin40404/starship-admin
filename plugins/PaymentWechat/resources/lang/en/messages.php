<?php

return [
    'config_incomplete' => 'Please configure AppID, merchant ID and APIv3 key first',
    'test_success' => 'WeChat Pay sample connectivity test passed',
    'test_failed' => 'WeChat Pay sample connectivity test failed',
    'order_no_required' => 'Business order number is required',
    'sandbox_order_subject' => 'Starship payment plugin test',
    'fields' => [
        'app_id_label' => 'AppID',
        'mch_id_label' => 'Merchant ID',
        'api_v3_key_label' => 'APIv3 key',
        'serial_no_label' => 'Certificate serial',
        'serial_no_help' => 'Required in production; optional in sandbox',
        'notify_url_label' => 'Notify URL',
        'notify_url_help' => 'Optional for sandbox tests',
        'sandbox_label' => 'Sandbox mode',
        'sandbox_help' => 'When enabled, create/query orders locally without calling WeChat',
    ],
];
