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

        return ApiResponse::success($result, '插件发现完成');
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
            return ApiResponse::success($pluginService->install($slug), '安装成功');
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function enable(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->enable($slug), '已启用，请重启后端服务后生效');
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function disable(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->disable($slug), '已禁用，请重启后端服务后生效');
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function uninstall(string $slug, PluginService $pluginService): JsonResponse
    {
        try {
            return ApiResponse::success($pluginService->uninstall($slug), '卸载成功');
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
                '配置已保存',
            );
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }
}
