<?php

namespace Buildcode\LaravelDatabaseEmails\Decorators;

use Buildcode\LaravelDatabaseEmails\Config;
use Buildcode\LaravelDatabaseEmails\Email;

class PrepareEmail implements EmailDecorator
{
    private $email;

    public function __construct(Email $email)
    {
        $this->email = $email;

        if ($email->hasCc()) {
            if (!is_array($email->cc)) {
                $email->cc = [$email->cc];
            }

            if (Config::testing()) {
                $email->cc = array_map(function () {
                    return Config::testEmailAddress();
                }, $email->cc);
            }

            $email->cc = json_encode($email->cc);
        }

        if ($email->hasBcc()) {
            if (!is_array($email->bcc)) {
                $email->bcc = [$email->bcc];
            }

            if (Config::testing()) {
                $email->bcc = array_map(function () {
                    return Config::testEmailAddress();
                }, $email->bcc);
            }

            $email->bcc = json_encode($email->bcc);
        }


        $email->body = view($email->getView(), $email->hasVariables() ? $email->getVariables() : [])->render();

        $email->variables = json_encode($email->getVariables());

        if ($email->isScheduled()) {
            $email->scheduled_at = $email->getScheduledDateAsCarbon()->toDateTimeString();
        }

        if (Config::testing()) {
            $email->recipient = Config::testEmailAddress();
        }

        $this->email->encrypted = Config::encryptEmails();
    }

    public function getEmail()
    {
        return $this->email;
    }
}