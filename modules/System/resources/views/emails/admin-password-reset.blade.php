<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>{{ __('system::messages.password_reset_mail_subject', ['site' => $siteName]) }}</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
<p>{{ __('system::messages.password_reset_mail_greeting', ['name' => $admin->nickname ?: $admin->username]) }}</p>
<p>{{ __('system::messages.password_reset_mail_body') }}</p>
<p>
    <a href="{{ $resetUrl }}" style="color: #1677ff;">{{ $resetUrl }}</a>
</p>
<p>{{ __('system::messages.password_reset_mail_expire', ['minutes' => 60]) }}</p>
<p>{{ __('system::messages.password_reset_mail_ignore') }}</p>
</body>
</html>
