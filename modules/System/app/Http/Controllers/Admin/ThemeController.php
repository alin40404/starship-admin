<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\System\App\Services\ThemeService;
use Starship\Core\Support\ApiResponse;

class ThemeController extends Controller
{
    public function index(ThemeService $themeService): JsonResponse
    {
        return ApiResponse::success($themeService->list());
    }

    public function discover(ThemeService $themeService): JsonResponse
    {
        $result = $themeService->discover();

        return ApiResponse::success($result, __('system::messages.theme_discover_done'));
    }

    public function show(string $slug, ThemeService $themeService): JsonResponse
    {
        try {
            return ApiResponse::success($themeService->show($slug));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 404);
        }
    }

    public function install(string $slug, ThemeService $themeService): JsonResponse
    {
        try {
            return ApiResponse::success($themeService->install($slug), __('system::messages.theme_installed'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function enable(string $slug, ThemeService $themeService): JsonResponse
    {
        try {
            return ApiResponse::success($themeService->enable($slug), __('system::messages.theme_enabled'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function disable(string $slug, ThemeService $themeService): JsonResponse
    {
        try {
            return ApiResponse::success($themeService->disable($slug), __('system::messages.theme_disabled'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function uninstall(string $slug, ThemeService $themeService): JsonResponse
    {
        try {
            return ApiResponse::success($themeService->uninstall($slug), __('system::messages.theme_uninstalled'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }

    public function switch(string $slug, Request $request, ThemeService $themeService): JsonResponse
    {
        try {
            $type = $request->input('type');
            $type = is_string($type) && $type !== '' ? $type : null;

            return ApiResponse::success(
                $themeService->switch($slug, $type),
                __('system::messages.theme_switched'),
            );
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }
}
