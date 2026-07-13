<?php

namespace Plugins\SmsAliyun\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Starship\Core\Support\ApiResponse;

class SmsAliyunController extends Controller
{
    public function test(Request $request, SmsSenderInterface $smsSender): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        $code = (string) random_int(100000, 999999);

        try {
            $smsSender->sendVerificationCode($data['mobile'], $code);
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('sms-aliyun::messages.send_failed'),
                422,
            );
        }

        return ApiResponse::success([
            'mobile' => $data['mobile'],
        ], __('sms-aliyun::messages.test_success'));
    }
}
