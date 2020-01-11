<?php

namespace Stackkit\LaravelDatabaseEmails;

use Exception;
use function call_user_func_array;
use Illuminate\Container\Container;

class MailableReader
{
    /**
     * Read the mailable and pass the data to the email composer.
     *
     * @param EmailComposer $composer
     */
    public function read(EmailComposer $composer)
    {
        Container::getInstance()->call([$composer->getData('mailable'), 'build']);

        $this->readRecipient($composer);

        $this->readFrom($composer);

        $this->readCc($composer);

        $this->readBcc($composer);

        $this->readSubject($composer);

        $this->readBody($composer);

        $this->readAttachments($composer);
    }

    /**
     * Convert the mailable addresses array into a array with only e-mails.
     *
     * @param string $from
     * @return array
     */
    private function convertMailableAddresses($from)
    {
        return collect($from)->map(function ($recipient) {
            return $recipient['address'];
        })->toArray();
    }

    /**
     * Read the mailable recipient to the email composer.
     *
     * @param EmailComposer $composer
     */
    private function readRecipient(EmailComposer $composer)
    {
        $to = $this->convertMailableAddresses(
            $composer->getData('mailable')->to
        );

        $composer->recipient($to);
    }

    /**
     * Read the mailable from field to the email composer.
     *
     * @param EmailComposer $composer
     */
    private function readFrom(EmailComposer $composer)
    {
        $from = reset($composer->getData('mailable')->from);

        if (!$from) {
            return;
        }

        $composer->from(
            $from['address'],
            $from['name']
        );
    }

    /**
     * Read the mailable cc to the email composer.
     *
     * @param EmailComposer $composer
     */
    private function readCc(EmailComposer $composer)
    {
        $cc = $this->convertMailableAddresses(
            $composer->getData('mailable')->cc
        );

        $composer->cc($cc);
    }

    /**
     * Read the mailable bcc to the email composer.
     *
     * @param EmailComposer $composer
     */
    private function readBcc(EmailComposer $composer)
    {
        $bcc = $this->convertMailableAddresses(
            $composer->getData('mailable')->bcc
        );

        $composer->bcc($bcc);
    }

    /**
     * Read the mailable subject to the email composer.
     *
     * @param EmailComposer $composer
     */
    private function readSubject(EmailComposer $composer)
    {
        $composer->subject($composer->getData('mailable')->subject);
    }

    /**
     * Read the mailable body to the email composer.
     *
     * @param EmailComposer $composer
     * @throws Exception
     */
    private function readBody(EmailComposer $composer)
    {
        if (app()->version() < '5.5') {
            throw new Exception('Mailables cannot be read by Laravel 5.4 and below. Sorry.');
        }

        $composer->setData('view', '');

        $mailable = $composer->getData('mailable');

        $composer->setData('body', view($mailable->view, $mailable->buildViewData()));
    }

    /**
     * Read the mailable attachments to the email composer.
     *
     * @param EmailComposer $composer
     */
    private function readAttachments(EmailComposer $composer)
    {
        $mailable = $composer->getData('mailable');

        foreach ((array) $mailable->attachments as $attachment) {
            call_user_func_array([$composer, 'attach'], $attachment);
        }

        foreach ((array) $mailable->rawAttachments as $rawAttachment) {
            call_user_func_array([$composer, 'attachData'], $rawAttachment);
        }
    }
}
