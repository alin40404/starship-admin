<?php

return [
    'access_key' => env('PLUGIN_STORAGE_OSS_ACCESS_KEY', ''),
    'secret_key' => env('PLUGIN_STORAGE_OSS_SECRET_KEY', ''),
    'bucket' => env('PLUGIN_STORAGE_OSS_BUCKET', ''),
    'endpoint' => env('PLUGIN_STORAGE_OSS_ENDPOINT', ''),
    'region' => env('PLUGIN_STORAGE_OSS_REGION', 'cn-hangzhou'),
    'cdn_url' => env('PLUGIN_STORAGE_OSS_CDN_URL', ''),
    'prefix' => env('PLUGIN_STORAGE_OSS_PREFIX', ''),
];
