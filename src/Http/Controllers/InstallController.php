<?php

namespace Starship\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use RuntimeException;
use Starship\Core\Install\InstallService;
use Starship\Core\Support\ApiResponse;
use Throwable;

class InstallController extends Controller
{
    public function status(InstallService $install): JsonResponse
    {
        return ApiResponse::success($install->status());
    }

    public function requirements(InstallService $install): JsonResponse
    {
        try {
            return ApiResponse::success($install->checkEnvironment());
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 403);
        }
    }

    public function database(Request $request, InstallService $install): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'connection' => ['required', 'string', 'in:mysql,sqlite,pgsql'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable'],
            'database' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:32'],
            'app_url' => ['nullable', 'string', 'max:255'],
            'cors_allowed_origins' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        try {
            $result = $install->saveDatabaseConfig($validator->validated());

            return ApiResponse::success($result, '数据库配置已保存并连接成功');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return ApiResponse::error('保存数据库配置失败：'.$exception->getMessage(), 500);
        }
    }

    public function run(Request $request, InstallService $install): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['nullable', 'string', 'max:64'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        try {
            $result = $install->runInstall($validator->validated());

            return ApiResponse::success($result, '安装完成');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return ApiResponse::error('安装失败：'.$exception->getMessage(), 500);
        }
    }
}
