<?php

return [
    'config_incomplete' => '请先完善 AccessKey、SecretKey 与 Bucket',
    'test_success' => 'OSS 连通性测试通过',
    'test_failed' => 'OSS 连通性测试失败',
    'fields' => [
        'access_key_label' => 'AccessKey ID',
        'secret_key_label' => 'AccessKey Secret',
        'bucket_label' => 'Bucket',
        'endpoint_label' => 'Endpoint',
        'endpoint_help' => '可留空；默认按 Region 生成 https://oss-{region}.aliyuncs.com',
        'region_label' => 'Region',
        'cdn_url_label' => 'CDN / 自定义域名',
        'cdn_url_help' => '可选；填写后 url() 优先使用此前缀',
        'prefix_label' => '对象前缀',
        'prefix_help' => '可选；所有路径会自动加上此前缀',
    ],
];
