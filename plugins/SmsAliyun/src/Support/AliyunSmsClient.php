<?php

namespace Plugins\SmsAliyun\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 阿里云短信 HTTP 客户端（插件实现细节，不属于 Core 底座）。
 */
final class AliyunSmsClient
{
    /**
     * @param  array{access_key: string, secret_key: string, sign_name: string, template_code: string}  $credentials
     */
    public function sendVerificationCode(array $credentials, string $mobile, string $code): void
    {
        $mobile = trim($mobile);

        $params = [
            'AccessKeyId' => $credentials['access_key'],
            'Action' => 'SendSms',
            'Format' => 'JSON',
            'PhoneNumbers' => $mobile,
            'SignName' => $credentials['sign_name'],
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => Str::uuid()->toString(),
            'SignatureVersion' => '1.0',
            'TemplateCode' => $credentials['template_code'],
            'TemplateParam' => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Version' => '2017-05-25',
        ];

        $params['Signature'] = $this->signature($params, $credentials['secret_key']);

        $response = Http::timeout(15)
            ->asForm()
            ->post('https://dysmsapi.aliyuncs.com/', $params);

        $payload = $response->json();
        if (! $response->successful() || ($payload['Code'] ?? '') !== 'OK') {
            throw new \RuntimeException(
                (string) ($payload['Message'] ?? __('sms-aliyun::messages.send_failed')),
            );
        }
    }

    /**
     * @param  array<string, string>  $params
     */
    private function signature(array $params, string $secretKey): string
    {
        ksort($params);
        $canonicalized = '';
        foreach ($params as $key => $value) {
            $canonicalized .= '&'.$this->percentEncode((string) $key).'='.$this->percentEncode((string) $value);
        }

        $stringToSign = 'POST&%2F&'.$this->percentEncode(substr($canonicalized, 1));

        return base64_encode(hash_hmac('sha1', $stringToSign, $secretKey.'&', true));
    }

    private function percentEncode(string $value): string
    {
        return str_replace(
            ['+', '*', '%7E'],
            ['%20', '%2A', '~'],
            rawurlencode($value),
        );
    }
}
