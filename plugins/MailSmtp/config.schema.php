<?php

return [
    [
        'key' => 'host',
        'label' => __('mail-smtp::messages.fields.host_label'),
        'type' => 'string',
        'rules' => ['required'],
        'help' => __('mail-smtp::messages.fields.host_help'),
        'group' => 'connection',
    ],
    [
        'key' => 'port',
        'label' => __('mail-smtp::messages.fields.port_label'),
        'type' => 'number',
        'default' => '465',
        'group' => 'connection',
    ],
    [
        'key' => 'username',
        'label' => __('mail-smtp::messages.fields.username_label'),
        'type' => 'string',
        'rules' => ['required'],
        'group' => 'connection',
    ],
    [
        'key' => 'password',
        'label' => __('mail-smtp::messages.fields.password_label'),
        'type' => 'password',
        'encrypted' => true,
        'rules' => ['required'],
        'group' => 'connection',
    ],
    [
        'key' => 'encryption',
        'label' => __('mail-smtp::messages.fields.encryption_label'),
        'type' => 'select',
        'options' => [
            ['value' => 'ssl', 'label' => __('mail-smtp::messages.fields.encryption_ssl')],
            ['value' => 'tls', 'label' => __('mail-smtp::messages.fields.encryption_tls')],
            ['value' => 'none', 'label' => __('mail-smtp::messages.fields.encryption_none')],
        ],
        'default' => 'ssl',
        'group' => 'connection',
    ],
    [
        'key' => 'from_address',
        'label' => __('mail-smtp::messages.fields.from_address_label'),
        'type' => 'string',
        'help' => __('mail-smtp::messages.fields.from_address_help'),
        'group' => 'sender',
    ],
    [
        'key' => 'from_name',
        'label' => __('mail-smtp::messages.fields.from_name_label'),
        'type' => 'string',
        'group' => 'sender',
    ],
];
