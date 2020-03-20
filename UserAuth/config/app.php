<?php

return [
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'dbname' => 'register',
        'username' => 'root',
        'password' => ''
    ],
    'mail' => [
        'transport' => 'smtp',
        'encrption' => 'tls',
        'port' => 587,
        'host' => 'smtp.gmail.com',
        'username' => 'your_gmail_email_address',
        'password' => 'your_gmail_password',
        'from' => 'no-reply@devscreencast.com',
        'sender_name' => 'User Authentication'
    ],
    'recaptcha' => [
        'key' => 'your_app_key',
        'secret' => 'your_app_secret',
    ]
];
