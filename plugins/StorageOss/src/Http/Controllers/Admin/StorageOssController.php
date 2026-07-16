<?php

namespace Plugins\StorageOss\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Starship\Core\Contracts\Storage\StorageInterface;
use Starship\Core\Support\ApiResponse;

class StorageOssController extends Controller
{
    public function test(StorageInterface $storage): JsonResponse
    {
        $path = 'starship-plugin-tests/storage-oss-'.Str::lower(Str::random(12)).'.txt';
        $payload = 'starship-storage-oss-'.now()->toIso8601String();

        try {
            if (! $storage->put($path, $payload)) {
                throw new \RuntimeException(__('storage-oss::messages.test_failed'));
            }

            if (! $storage->exists($path)) {
                throw new \RuntimeException(__('storage-oss::messages.test_failed'));
            }

            $url = $storage->url($path);
            $storage->delete($path);
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('storage-oss::messages.test_failed'),
                422,
            );
        }

        return ApiResponse::success([
            'path' => $path,
            'url' => $url,
        ], __('storage-oss::messages.test_success'));
    }
}
