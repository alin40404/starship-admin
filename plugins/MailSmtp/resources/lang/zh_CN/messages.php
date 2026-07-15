<?php

return [
    'config_incomplete' => '请先完善 SMTP 插件配置（主机、账号、密码）',
    'send_invalid' => '收件人或邮件主题无效',
    'send_failed' => '邮件发送失败',
    'test_subject' => 'MailSmtp 插件邮件测试',
    'test_body' => '这是一封由 MailSmtp 插件发出的测试邮件。',
    'test_success' => '测试成功',
    'fields' => [
        'host_label' => 'SMTP 主机',
        'host_help' => '例如 smtp.example.com',
        'port_label' => '端口',
        'username_label' => '用户名',
        'password_label' => '密码',
        'encryption_label' => '加密方式',
        'encryption_ssl' => 'SSL',
        'encryption_tls' => 'TLS',
        'encryption_none' => '无',
        'from_address_label' => '发件人邮箱',
        'from_address_help' => '留空则使用用户名',
        'from_name_label' => '发件人名称',
    ],
];
