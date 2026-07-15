<?php

return [
    'config_incomplete' => 'Complete the SMTP plugin settings (host, username, password) first.',
    'send_invalid' => 'Recipient or subject is invalid',
    'send_failed' => 'Failed to send email',
    'test_subject' => 'MailSmtp plugin mail test',
    'test_body' => 'This is a test email sent by the MailSmtp plugin.',
    'test_success' => 'Test succeeded',
    'fields' => [
        'host_label' => 'SMTP Host',
        'host_help' => 'e.g. smtp.example.com',
        'port_label' => 'Port',
        'username_label' => 'Username',
        'password_label' => 'Password',
        'encryption_label' => 'Encryption',
        'encryption_ssl' => 'SSL',
        'encryption_tls' => 'TLS',
        'encryption_none' => 'None',
        'from_address_label' => 'From Address',
        'from_address_help' => 'Defaults to username when empty',
        'from_name_label' => 'From Name',
    ],
];
