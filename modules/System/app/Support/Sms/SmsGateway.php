<?php

namespace Modules\System\App\Support\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\System\App\Support\SmsConfigRegistrar;
use Starship\Core\Contracts\Sms\SmsSenderInterface;

class SmsGateway implements SmsSenderInterface
{
    public function sendVerificationCode(string $mobile, string $code): void
    {
        $mobile = trim($mobile);

        match (SmsConfigRegistrar::provider()) {
            'aliyun' => $this->sendAliyun($mobile, $code),
            'tencent' => $this->sendTencent($mobile, $code),
            'huawei' => $this->sendHuawei($mobile, $code),
            default => throw new \RuntimeException(__('system::messages.sms_provider_unsupported')),
        };
    }

    private function sendAliyun(string $mobile, string $code): void
    {
        $params = [
            'AccessKeyId' => SmsConfigRegistrar::accessKey(),
            'Action' => 'SendSms',
            'Format' => 'JSON',
            'PhoneNumbers' => $mobile,
            'SignName' => SmsConfigRegistrar::signName(),
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => Str::uuid()->toString(),
            'SignatureVersion' => '1.0',
            'TemplateCode' => SmsConfigRegistrar::templateCode(),
            'TemplateParam' => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Version' => '2017-05-25',
        ];

        $params['Signature'] = $this->aliyunSignature($params, SmsConfigRegistrar::secretKey());

        $response = Http::timeout(15)
            ->asForm()
            ->post('https://dysmsapi.aliyuncs.com/', $params);

        $payload = $response->json();
        if (! $response->successful() || ($payload['Code'] ?? '') !== 'OK') {
            throw new \RuntimeException(
                (string) ($payload['Message'] ?? __('system::messages.sms_send_failed')),
            );
        }
    }

    private function sendTencent(string $mobile, string $code): void
    {
        $secretId = SmsConfigRegistrar::accessKey();
        $secretKey = SmsConfigRegistrar::secretKey();
        $region = SmsConfigRegistrar::region();
        $service = 'sms';
        $host = 'sms.tencentcloudapi.com';
        $action = 'SendSms';
        $version = '2021-01-11';
        $timestamp = time();
        $date = gmdate('Y-m-d', $timestamp);

        $body = json_encode([
            'PhoneNumberSet' => ['+86'.$mobile],
            'SmsSdkAppId' => SmsConfigRegistrar::appId(),
            'SignName' => SmsConfigRegistrar::signName(),
            'TemplateId' => SmsConfigRegistrar::templateCode(),
            'TemplateParamSet' => [$code],
        ], JSON_UNESCAPED_UNICODE);

        $headers = $this->tencentHeaders(
            $secretId,
            $secretKey,
            $host,
            $service,
            $region,
            $action,
            $version,
            $timestamp,
            $date,
            $body,
        );

        $response = Http::timeout(15)
            ->withHeaders($headers)
            ->withBody($body, 'application/json')
            ->post("https://{$host}");

        $payload = $response->json();
        $error = $payload['Response']['Error'] ?? null;
        if (! $response->successful() || is_array($error)) {
            throw new \RuntimeException(
                (string) ($error['Message'] ?? __('system::messages.sms_send_failed')),
            );
        }
    }

    private function sendHuawei(string $mobile, string $code): void
    {
        $region = SmsConfigRegistrar::region();
        $endpoint = "https://smsapi.{$region}.myhuaweicloud.com:443/sms/batchSendSms/v1";
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $nonce = Str::uuid()->toString();
        $digest = base64_encode(hash('sha256', $nonce.$created.SmsConfigRegistrar::secretKey(), true));

        $body = json_encode([
            'from' => SmsConfigRegistrar::appId(),
            'to' => ['+86'.$mobile],
            'templateId' => SmsConfigRegistrar::templateCode(),
            'templateParas' => [$code],
            'signature' => SmsConfigRegistrar::signName(),
        ], JSON_UNESCAPED_UNICODE);

        $response = Http::timeout(15)
            ->withHeaders([
                'Authorization' => 'WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
                'X-WSSE' => sprintf(
                    'UsernameToken Username="%s",PasswordDigest="%s",Nonce="%s",Created="%s"',
                    SmsConfigRegistrar::accessKey(),
                    $digest,
                    $nonce,
                    $created,
                ),
                'Content-Type' => 'application/json',
            ])
            ->withBody($body, 'application/json')
            ->post($endpoint);

        $payload = $response->json();
        $result = $payload['result'][0] ?? null;
        if (! $response->successful() || ! is_array($result) || ($result['status'] ?? '') !== '000000') {
            throw new \RuntimeException(
                (string) ($result['statusDesc'] ?? $payload['description'] ?? __('system::messages.sms_send_failed')),
            );
        }
    }

    /**
     * @param  array<string, string>  $params
     */
    private function aliyunSignature(array $params, string $secretKey): string
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

    /**
     * @return array<string, string>
     */
    private function tencentHeaders(
        string $secretId,
        string $secretKey,
        string $host,
        string $service,
        string $region,
        string $action,
        string $version,
        int $timestamp,
        string $date,
        string $body,
    ): array {
        $hashedRequestPayload = hash('sha256', $body);
        $canonicalHeaders = "content-type:application/json\nhost:{$host}\n";
        $signedHeaders = 'content-type;host';
        $canonicalRequest = implode("\n", [
            'POST',
            '/',
            '',
            $canonicalHeaders,
            $signedHeaders,
            $hashedRequestPayload,
        ]);

        $credentialScope = "{$date}/{$service}/tc3_request";
        $stringToSign = implode("\n", [
            'TC3-HMAC-SHA256',
            (string) $timestamp,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $secretDate = hash_hmac('sha256', $date, 'TC3'.$secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);

        $authorization = sprintf(
            'TC3-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $secretId,
            $credentialScope,
            $signedHeaders,
            $signature,
        );

        return [
            'Authorization' => $authorization,
            'Content-Type' => 'application/json',
            'Host' => $host,
            'X-TC-Action' => $action,
            'X-TC-Timestamp' => (string) $timestamp,
            'X-TC-Version' => $version,
            'X-TC-Region' => $region,
        ];
    }
}
