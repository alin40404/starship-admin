<?php

return [
    [
        'key' => 'access_key',
        'label' => __('storage-oss::messages.fields.access_key_label'),
        'type' => 'string',
        'rules' => ['required'],
        'group' => 'credentials',
    ],
    [
        'key' => 'secret_key',
        'label' => __('storage-oss::messages.fields.secret_key_label'),
        'type' => 'password',
        'encrypted' => true,
        'rules' => ['required'],
        'group' => 'credentials',
    ],
    [
        'key' => 'bucket',
        'label' => __('storage-oss::messages.fields.bucket_label'),
        'type' => 'string',
        'rules' => ['required'],
        'group' => 'bucket',
    ],
    [
        'key' => 'endpoint',
        'label' => __('storage-oss::messages.fields.endpoint_label'),
        'type' => 'string',
        'help' => __('storage-oss::messages.fields.endpoint_help'),
        'group' => 'bucket',
    ],
    [
        'key' => 'region',
        'label' => __('storage-oss::messages.fields.region_label'),
        'type' => 'string',
        'default' => 'cn-hangzhou',
        'group' => 'bucket',
    ],
    [
        'key' => 'cdn_url',
        'label' => __('storage-oss::messages.fields.cdn_url_label'),
        'type' => 'string',
        'help' => __('storage-oss::messages.fields.cdn_url_help'),
        'group' => 'bucket',
    ],
    [
        'key' => 'prefix',
        'label' => __('storage-oss::messages.fields.prefix_label'),
        'type' => 'string',
        'help' => __('storage-oss::messages.fields.prefix_help'),
        'group' => 'bucket',
    ],
];
