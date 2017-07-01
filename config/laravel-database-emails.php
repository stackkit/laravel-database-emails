<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retry Mode
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of attempts the cronjob should get
    | to send an email. If the sending fails after the number of max
    | tries, we will no longer attempt to send that e-mail.
    |
    */

    'retry' => [

        'attempts' => 3,

    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Here you may enable encryption for all e-mails. If enabled, we
    | will automatically encrypt all the view data, recipient and
    | decrypt it during the sending phase.
    |
    */

    'encrypt' => false,

    /*
    |--------------------------------------------------------------------------
    | Test E-mail
    |--------------------------------------------------------------------------
    |
    | When developing the application or testing on a staging server you may
    | wish to send all e-mails to a specific test inbox. If enabled, all
    | recpient emails will be hijacked and sent to the test address.
    |
    */

    'testing' => [

        'email' => 'test@email.com',

        'enabled' => function () {
            return app()->environment('local', 'staging');
        }

    ],

    /*
    |--------------------------------------------------------------------------
    | Cronjob Limit
    |--------------------------------------------------------------------------
    |
    | Limit the number of e-mails the cronjob may send at a time. This is useful
    | if you want to prevent overlapping cronjobs. Keep in mind we already
    | handle overlapping gracefully, however setting a limit is adviced.
    |
    */

    'limit' => 20
];
