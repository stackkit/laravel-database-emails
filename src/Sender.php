<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

class Sender
{
    /**
     * Send the given e-mail.
     *
     * @param Email $email
     */
    public function send(Email $email)
    {
        if ($email->isSent()) {
            return;
        }

        $email->markAsSending();

        if (app()->runningUnitTests()) {
            Event::dispatch('before.send');
        }

        Mail::send([], [], function (Message $message) use ($email) {
            $message->to($email->getRecipient())
                ->cc($email->hasCc() ? $email->getCc() : [])
                ->bcc($email->hasBcc() ? $email->getBcc() : [])
                ->subject($email->getSubject())
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->setBody($email->getBody(), 'text/html');

            foreach ((array)$email->getAttachments() as $attachment) {
                if ($attachment['type'] == 'attachment') {
                    $message->attach($attachment['attachment']['file'], $attachment['attachment']['options']);
                } else if($attachment['type'] == 'rawAttachment') {
                    $message->attachData($attachment['attachment']['data'], $attachment['attachment']['name'], $attachment['attachment']['options']);
                }
            }
        });

        $email->markAsSent();
    }
}
