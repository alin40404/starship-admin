<?php

return [
    'config_incomplete' => 'Please configure AccessKey, SecretKey and Bucket first',
    'test_success' => 'OSS connectivity test passed',
    'test_failed' => 'OSS connectivity test failed',
    'fields' => [
        'access_key_label' => 'AccessKey ID',
        'secret_key_label' => 'AccessKey Secret',
        'bucket_label' => 'Bucket',
        'endpoint_label' => 'Endpoint',
        'endpoint_help' => 'Optional; defaults to https://oss-{region}.aliyuncs.com',
        'region_label' => 'Region',
        'cdn_url_label' => 'CDN / custom domain',
        'cdn_url_help' => 'Optional; when set, url() prefers this prefix',
        'prefix_label' => 'Object prefix',
        'prefix_help' => 'Optional; prepended to all object paths',
    ],
];
