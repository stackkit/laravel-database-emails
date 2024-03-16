<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

class Encrypter
{
    /**
     * Encrypt the given e-mail.
     */
    public function encrypt(EmailComposer $composer): void
    {
        $this->setEncrypted($composer);

        $this->encryptRecipients($composer);

        $this->encryptReplyTo($composer);

        $this->encryptFrom($composer);

        $this->encryptSubject($composer);

        $this->encryptVariables($composer);

        $this->encryptBody($composer);
    }

    /**
     * Mark the e-mail as encrypted.
     */
    private function setEncrypted(EmailComposer $composer): void
    {
        $composer->getEmail()->setAttribute('encrypted', 1);
    }

    /**
     * Encrypt the e-mail reply-to.
     */
    private function encryptReplyTo(EmailComposer $composer): void
    {
        $email = $composer->getEmail();

        $email->fill([
            'reply_to' => $composer->hasData('reply_to') ? encrypt($email->reply_to) : '',
        ]);
    }

    /**
     * Encrypt the e-mail addresses of the recipients.
     */
    private function encryptRecipients(EmailComposer $composer): void
    {
        $email = $composer->getEmail();

        $email->fill([
            'recipient' => encrypt($email->recipient),
            'cc' => $composer->hasData('cc') ? encrypt($email->cc) : '',
            'bcc' => $composer->hasData('bcc') ? encrypt($email->bcc) : '',
        ]);
    }

    /**
     * Encrypt the e-mail addresses for the from field.
     */
    private function encryptFrom(EmailComposer $composer): void
    {
        $email = $composer->getEmail();

        $email->fill([
            'from' => encrypt($email->from),
        ]);
    }

    /**
     * Encrypt the e-mail subject.
     */
    private function encryptSubject(EmailComposer $composer): void
    {
        $email = $composer->getEmail();

        $email->fill([
            'subject' => encrypt($email->subject),
        ]);
    }

    /**
     * Encrypt the e-mail variables.
     */
    private function encryptVariables(EmailComposer $composer): void
    {
        if (! $composer->hasData('variables')) {
            return;
        }

        $email = $composer->getEmail();

        $email->fill([
            'variables' => encrypt($email->variables),
        ]);
    }

    /**
     * Encrypt the e-mail body.
     */
    private function encryptBody(EmailComposer $composer): void
    {
        $email = $composer->getEmail();

        $email->fill([
            'body' => encrypt($email->body),
        ]);
    }
}
