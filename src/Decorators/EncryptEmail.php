<?php

namespace Buildcode\LaravelDatabaseEmails\Decorators;

class EncryptEmail implements EmailDecorator
{
    private $email;

    public function __construct(EmailDecorator $decorator)
    {
        $this->email = $decorator->getEmail();

        $this->email->fill([
            'recipient' => encrypt($this->email->getRecipient()),
            'subject'   => encrypt($this->email->getSubject()),
            'variables' => encrypt(json_encode($this->email->getVariables())),
            'body'      => encrypt(view($this->email->getView(), $this->email->getVariables())->render()),
        ]);
    }

    public function getEmail()
    {
        return $this->email;
    }


}