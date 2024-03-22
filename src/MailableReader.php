<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Error;
use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Traits\Localizable;

class MailableReader
{
    use Localizable;

    /**
     * Read the mailable and pass the data to the email composer.
     */
    public function read(EmailComposer $composer): void
    {
        if ($composer->envelope && $composer->content) {
            $composer->setData('mailable', new class($composer) extends Mailable
            {
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

                public function attachments(): array
                {
                    return $this->composer->attachments ?? [];
                }
            });
        }

        (fn (Mailable $mailable) => $mailable->prepareMailableForDelivery())->call(
            $composer->getData('mailable'),
            $composer->getData('mailable'),
        );

        $this->readRecipient($composer);

        $this->readFrom($composer);

        $this->readCc($composer);

        $this->readBcc($composer);

        $this->readReplyTo($composer);

        $this->readSubject($composer);

        $this->readBody($composer);

        $this->readAttachments($composer);

        $this->readModel($composer);
    }

    /**
     * Convert the mailable addresses array into a array with only e-mails.
     *
     * @param  string  $from
     */
    private function convertMailableAddresses($from): array
    {
        return collect($from)->mapWithKeys(function ($recipient) {
            return [$recipient['address'] => $recipient['name']];
        })->toArray();
    }

    /**
     * Read the mailable recipient to the email composer.
     */
    private function readRecipient(EmailComposer $composer): void
    {
        if (config('database-emails.testing.enabled')) {
            $composer->getEmail()->recipient = [
                config('database-emails.testing.email') => null,
            ];

            return;
        }

        $composer->getEmail()->recipient = $this->prepareAddressForDatabaseStorage(
            $composer->getData('mailable')->to);
    }

    /**
     * Read the mailable from field to the email composer.
     */
    private function readFrom(EmailComposer $composer): void
    {
        $composer->getEmail()->from = head($composer->getData('mailable')->from);
    }

    /**
     * Read the mailable cc to the email composer.
     */
    private function readCc(EmailComposer $composer): void
    {
        $composer->getEmail()->cc = $this->prepareAddressForDatabaseStorage(
            $composer->getData('mailable')->cc);
    }

    /**
     * Read the mailable bcc to the email composer.
     */
    private function readBcc(EmailComposer $composer): void
    {
        $composer->getEmail()->bcc = $this->prepareAddressForDatabaseStorage(
            $composer->getData('mailable')->bcc);
    }

    /**
     * Read the mailable reply-to to the email composer.
     */
    private function readReplyTo(EmailComposer $composer): void
    {
        $composer->getEmail()->reply_to = $this->prepareAddressForDatabaseStorage(
            $composer->getData('mailable')->replyTo);
    }

    /**
     * Read the mailable subject to the email composer.
     */
    private function readSubject(EmailComposer $composer): void
    {
        $composer->getEmail()->subject = $composer->getData('mailable')->subject;
    }

    /**
     * Read the mailable body to the email composer.
     *
     * @throws Exception
     */
    private function readBody(EmailComposer $composer): void
    {
        /** @var Mailable $mailable */
        $mailable = $composer->getData('mailable');

        $composer->getEmail()->view = $mailable->view;
        $composer->getEmail()->variables = $mailable->buildViewData();

        $localeToUse = $composer->locale ?? app()->currentLocale();

        $this->withLocale(
            $localeToUse,
            fn () => $composer->getEmail()->body = view($mailable->view, $mailable->buildViewData())->render(),
        );
    }

    /**
     * Read the mailable attachments to the email composer.
     */
    private function readAttachments(EmailComposer $composer): void
    {
        $mailable = $composer->getData('mailable');

        $composer->getEmail()->attachments = array_map(function (array $attachment) {
            if (! $attachment['file'] instanceof Attachment) {
                throw new Error('The attachment is not an instance of '.Attachment::class.'.');
            }

            return $attachment['file']->toArray();
        }, $mailable->attachments);
    }

    public function readModel(EmailComposer $composer): void
    {
        if ($composer->hasData('model')) {
            $composer->getEmail()->model()->associate($composer->getData('model'));
        }
    }

    private function prepareAddressForDatabaseStorage(array $addresses): array
    {
        return collect($addresses)->mapWithKeys(function ($recipient) {
            return [$recipient['address'] => $recipient['name']];
        })->toArray();
    }
}
