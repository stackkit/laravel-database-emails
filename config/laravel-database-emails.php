<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attempts
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of times the cronjob will try to send an e-mail.
    | Once the max attempt count is reached, the e-mail will be marked as failed
    | and will no longer be sent.
    |
    */

    'attempts' => 3,

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Here you may enable encryption for all e-mails. The e-mail will be encrypted according
    | your application's configuration (OpenSSL AES-256-CBC by default).
    |
    */

    'encrypt' => false,

    /*
    |--------------------------------------------------------------------------
    | Test E-mail
    |--------------------------------------------------------------------------
    |
    | When developing your application or testing on a staging server you may
    | wish to send all e-mails to a specific test inbox. Once enabled, every
    | newly created e-mail will be sent to the specified test address.
    |
    */

    'testing' => [

        'email' => 'test@email.com',

        'enabled' => env('LARAVEL_DATABASE_EMAILS_TESTING_ENABLED', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Cronjob Limit
    |--------------------------------------------------------------------------
    |
    | Limit the number of e-mails that should be sent at a time. Please ajust this
    | configuration based on the number of e-mails you expect to send and
    | the throughput of your e-mail sending provider.
    |
    */

    'limit' => 20,

    /*
    |--------------------------------------------------------------------------
    | Send E-mails Immediately
    |--------------------------------------------------------------------------
    |
    | Sends e-mails immediately after calling send() or schedule(). Useful for development
    | when you don't have Laravel Scheduler running or don't want to wait up to
    | 60 seconds for each e-mail to be sent.
    |
    */

    'immediately' => env('LARAVEL_DATABASE_EMAILS_SEND_IMMEDIATELY', false),
];
