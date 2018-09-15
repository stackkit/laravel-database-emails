<?php

namespace Stackkit\LaravelDatabaseEmails;

class Encrypter
{
    /**
     * Encrypt the given e-mail.
     *
     * @param EmailComposer $composer
     */
    public function encrypt(EmailComposer $composer)
    {
        $this->setEncrypted($composer);

        $this->encryptRecipients($composer);

        $this->encryptFrom($composer);

        $this->encryptSubject($composer);

        $this->encryptVariables($composer);

        $this->encryptBody($composer);
    }

    /**
     * Mark the e-mail as encrypted.
     *
     * @param EmailComposer $composer
     */
    private function setEncrypted(EmailComposer $composer)
    {
        $composer->getEmail()->setAttribute('encrypted', 1);
    }

    /**
     * Encrypt the e-mail addresses of the recipients.
     *
     * @param EmailComposer $composer
     */
    private function encryptRecipients(EmailComposer $composer)
    {
        $email = $composer->getEmail();

        $email->fill([
            'recipient' => encrypt($email->recipient),
            'cc'        => $composer->hasData('cc') ? encrypt($email->cc) : '',
            'bcc'       => $composer->hasData('bcc') ? encrypt($email->bcc) : '',
        ]);
    }

    /**
     * Encrypt the e-mail addresses for the from field.
     *
     * @param EmailComposer $composer
     */
    private function encryptFrom(EmailComposer $composer)
    {
        $email = $composer->getEmail();

        $email->fill([
            'from' => encrypt($email->from),
        ]);
    }

    /**
     * Encrypt the e-mail subject.
     *
     * @param EmailComposer $composer
     */
    private function encryptSubject(EmailComposer $composer)
    {
        $email = $composer->getEmail();

        $email->fill([
            'subject' => encrypt($email->subject),
        ]);
    }

    /**
     * Encrypt the e-mail variables.
     *
     * @param EmailComposer $composer
     */
    private function encryptVariables(EmailComposer $composer)
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
     *
     * @param EmailComposer $composer
     */
    private function encryptBody(EmailComposer $composer)
    {
        $email = $composer->getEmail();

        $email->fill([
            'body' => encrypt($email->body),
        ]);
    }
}
