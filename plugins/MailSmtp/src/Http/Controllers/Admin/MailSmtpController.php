<?php

namespace Plugins\MailSmtp\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Starship\Core\Contracts\Mail\MailSenderInterface;
use Starship\Core\Support\ApiResponse;

class MailSmtpController extends Controller
{
    public function test(Request $request, MailSenderInterface $mailSender): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        try {
            $mailSender->send(
                $data['email'],
                __('mail-smtp::messages.test_subject'),
                __('mail-smtp::messages.test_body'),
                ['html' => false],
            );
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('mail-smtp::messages.send_failed'),
                422,
            );
        }

        return ApiResponse::success([
            'email' => $data['email'],
        ], __('mail-smtp::messages.test_success'));
    }
}
