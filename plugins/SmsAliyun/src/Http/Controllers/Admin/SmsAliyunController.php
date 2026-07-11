<?php

namespace Plugins\SmsAliyun\Http\Controllers\Admin;

use Starship\Core\Plugin\PluginConfigRepository;
use App\Http\Controllers\Controller;
use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsAliyunController extends Controller
{
    public function test(Request $request, PluginConfigRepository $pluginConfigRepository): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        $accessKey = (string) $pluginConfigRepository->get('sms-aliyun', 'access_key', '');
        $secretKey = (string) $pluginConfigRepository->get('sms-aliyun', 'secret_key', '');
        $signName = (string) $pluginConfigRepository->get('sms-aliyun', 'sign_name', '');
        $templateCode = (string) $pluginConfigRepository->get('sms-aliyun', 'template_code', '');

        if ($accessKey === '' || $secretKey === '' || $signName === '' || $templateCode === '') {
            return ApiResponse::error('请先完善短信插件配置', 422);
        }

        return ApiResponse::success([
            'mobile' => $data['mobile'],
            'sign_name' => $signName,
            'template_code' => $templateCode,
            'message' => '测试请求已通过插件路由校验（第一版不调用真实阿里云 SDK）',
        ], '测试成功');
    }
}
