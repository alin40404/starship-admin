<?php

namespace Modules\System\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\System\App\Services\DevModuleService;

class DevModuleController extends Controller
{
    public function index(DevModuleService $devModuleService): JsonResponse
    {
        return ApiResponse::success($devModuleService->list());
    }

    public function show(string $name, DevModuleService $devModuleService): JsonResponse
    {
        try {
            return ApiResponse::success($devModuleService->show($name));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 404);
        }
    }

    public function updateStatus(string $name, Request $request, DevModuleService $devModuleService): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        try {
            $module = $devModuleService->updateEnabled($name, (bool) $data['enabled']);

            return ApiResponse::success($module, __('system::messages.module_restart_required'));
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }
    }
}
