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

        if ($this->email->hasCc()) {
            $this->email->cc = encrypt($this->email->getCc());
        }

        if ($this->email->hasBcc()) {
            $this->email->bcc = encrypt($this->email->getBcc());
        }
    }

    public function getEmail()
    {
        return $this->email;
    }


}