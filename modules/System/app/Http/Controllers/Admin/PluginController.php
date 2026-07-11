<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\System\App\Services\PluginService;

class PluginController extends Controller
{
    public function index(PluginService $pluginService): JsonResponse
    {
        return ApiResponse::success($pluginService->list());
    }

    public function discover(PluginService $pluginService): JsonResponse
    {
        $result = $pluginService->discover();

        return ApiResponse::success($result, __('system::messages.plugin_discover_done'));
    }

    public function show(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->show($slug));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 404);
        }
    }

    public function install(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->install($slug), __('system::messages.plugin_installed'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function enable(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->enable($slug), __('system::messages.plugin_enabled'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function disable(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->disable($slug), __('system::messages.plugin_disabled'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function uninstall(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->uninstall($slug), __('system::messages.plugin_uninstalled'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function config(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->getConfig($slug));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 404);
        }
    }

    public function updateConfig(string $slug, Request $request, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success(
                $pluginService->updateConfig($slug, $request->all()),
                __('system::messages.plugin_config_saved'),
            );
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }
}
