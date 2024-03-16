<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use ReflectionObject;

class MailableReader
{
    /**
     * Read the mailable and pass the data to the email composer.
     */
    public function read(EmailComposer $composer): void
    {
        if ($composer->envelope && $composer->content) {
            $composer->setData('mailable', new class($composer) extends Mailable {
                public function __construct(private EmailComposer $composer)
                {
                    //
                }

                public function content(): Content
                {
                    return $this->composer->content;
                }

                public function envelope(): Envelope
                {
                    return $this->composer->envelope;
                }
            });
        }


        if (method_exists($composer->getData('mailable'), 'prepareMailableForDelivery')) {
            $reflected = (new ReflectionObject($composer->getData('mailable')));
            $method = $reflected->getMethod('prepareMailableForDelivery');
            $method->setAccessible(true);
            $method->invoke($composer->getData('mailable'));
        } else {
            Container::getInstance()->call([$composer->getData('mailable'), 'build']);
        }

        $this->readRecipient($composer);

        $this->readFrom($composer);

        $this->readCc($composer);

        $this->readBcc($composer);

        $this->readReplyTo($composer);

        $this->readSubject($composer);

        $this->readBody($composer);

        $this->readAttachments($composer);
    }

    /**
     * Convert the mailable addresses array into a array with only e-mails.
     *
     * @param  string  $from
     */
    private function convertMailableAddresses($from): array
    {
        return collect($from)->map(function ($recipient) {
            return $recipient['address'];
        })->toArray();
    }

    /**
     * Read the mailable recipient to the email composer.
     */
    private function readRecipient(EmailComposer $composer): void
    {
        $to = $this->convertMailableAddresses(
            $composer->getData('mailable')->to
        );

        $composer->recipient($to);
    }

    /**
     * Read the mailable from field to the email composer.
     */
    private function readFrom(EmailComposer $composer): void
    {
        $from = reset($composer->getData('mailable')->from);

        if (! $from) {
            return;
        }

        $composer->from(
            $from['address'],
            $from['name']
        );
    }

    /**
     * Read the mailable cc to the email composer.
     */
    private function readCc(EmailComposer $composer): void
    {
        $cc = $this->convertMailableAddresses(
            $composer->getData('mailable')->cc
        );

        $composer->cc($cc);
    }

    /**
     * Read the mailable bcc to the email composer.
     */
    private function readBcc(EmailComposer $composer): void
    {
        $bcc = $this->convertMailableAddresses(
            $composer->getData('mailable')->bcc
        );

        $composer->bcc($bcc);
    }

    /**
     * Read the mailable reply-to to the email composer.
     */
    private function readReplyTo(EmailComposer $composer): void
    {
        $replyTo = $this->convertMailableAddresses(
            $composer->getData('mailable')->replyTo
        );

        $composer->replyTo($replyTo);
    }

    /**
     * Read the mailable subject to the email composer.
     */
    private function readSubject(EmailComposer $composer): void
    {
        $composer->subject($composer->getData('mailable')->subject);
    }

    /**
     * Read the mailable body to the email composer.
     *
     * @throws Exception
     */
    private function readBody(EmailComposer $composer): void
    {
        $composer->setData('view', '');

        $mailable = $composer->getData('mailable');

        $composer->setData('body', view($mailable->view, $mailable->buildViewData())->render());
    }

    /**
     * Read the mailable attachments to the email composer.
     */
    private function readAttachments(EmailComposer $composer): void
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
