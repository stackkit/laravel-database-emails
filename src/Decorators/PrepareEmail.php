<?php

namespace Buildcode\LaravelDatabaseEmails\Decorators;

use Buildcode\LaravelDatabaseEmails\Email;

class PrepareEmail implements EmailDecorator
{
    private $email;

    public function __construct(Email $email)
    {
        $this->email = $email;

        $email->body = view($email->getView(), $email->getVariables());

        $email->variables = json_encode($email->getVariables());

        $email->scheduled_at = $email->getScheduledDateAsCarbon()->toDateTimeString();

        $this->email->encrypted = config('laravel-database-emails.encrypt', false);

        $test = config('laravel-database-emails.testing.enabled', false);

        if ($test()) {
            $email->recipient = config('laravel-database-emails.testing.email');
        }
    }

    public function getEmail()
    {
        return $this->email;
    }
}