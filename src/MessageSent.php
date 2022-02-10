<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

class MessageSent
{
    /**
     * @var \Illuminate\Mail\SentMessage
     */
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
