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
            $install->assertWebInstallAllowed();

            return ApiResponse::success($install->checkEnvironment());
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 403);
        }
    }

    public function database(Request $request, InstallService $install): JsonResponse
    {
        try {
            $install->assertWebInstallAllowed();
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 403);
        }

        $connection = strtolower((string) $request->input('connection', ''));
        $serverRequired = in_array($connection, ['mysql', 'pgsql'], true);

        $validator = Validator::make($request->all(), [
            'connection' => ['required', 'string', 'in:mysql,sqlite,pgsql'],
            'host' => [$serverRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'port' => [$serverRequired ? 'required' : 'nullable', 'numeric'],
            'database' => ['required', 'string', 'max:255'],
            'username' => [$serverRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:32'],
            'app_url' => ['required', 'string', 'url', 'max:255'],
            'cors_allowed_origins' => ['nullable', 'string', 'max:2000'],
        ], [
            'connection.required' => '请选择数据库类型',
            'connection.in' => '不支持的数据库类型',
            'host.required' => '请填写数据库主机',
            'port.required' => '请填写数据库端口',
            'port.numeric' => '数据库端口必须是数字',
            'database.required' => $connection === 'sqlite' ? '请填写 SQLite 文件路径' : '请填写数据库名',
            'username.required' => '请填写数据库用户名',
            'app_url.required' => '请填写站点 URL',
            'app_url.url' => '站点 URL 格式不正确',
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
        try {
            $install->assertWebInstallAllowed();
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 403);
        }

        $validator = Validator::make($request->all(), [
            'step' => ['required', 'string', 'in:prepare,migrate,seed,finalize'],
            'site_name' => ['required', 'string', 'max:64'],
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'step.required' => '请指定安装步骤',
            'step.in' => '不支持的安装步骤',
            'site_name.required' => '请填写站点名称',
            'username.required' => '请填写超级管理员用户名',
            'password.required' => '请填写超级管理员密码',
            'password.min' => '超级管理员密码至少 6 位',
            'email.required' => '请填写超级管理员邮箱',
            'email.email' => '超级管理员邮箱格式不正确',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $payload = $validator->validated();
        $step = (string) $payload['step'];
        unset($payload['step']);

        try {
            $result = $install->runInstallStep($step, $payload);
            $message = ($result['installed'] ?? false) ? '安装完成' : '步骤完成：'.$step;

            return ApiResponse::success($result, $message);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return ApiResponse::error('安装失败：'.$exception->getMessage(), 500);
        }
    }
}
