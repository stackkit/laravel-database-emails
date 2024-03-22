<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Mail\Attachment;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class Sender
{
    /**
     * Send the given e-mail.
     */
    public function send(Email $email): void
    {
        if ($email->isSent()) {
            return;
        }

        $email->markAsSending();

        $sentMessage = Mail::send([], [], function (Message $message) use ($email) {
            $this->buildMessage($message, $email);
        });

        // $sentMessage is null when mocking (Mail::shouldReceive('send')->once())
        if (! is_null($sentMessage)) {
            event(new MessageSent($sentMessage));
        }

        $email->markAsSent();
    }

    /**
     * Build the e-mail message.
     */
    private function buildMessage(Message $message, Email $email): void
    {
        $message->to($email->recipient)
            ->cc($email->cc ?: [])
            ->bcc($email->bcc ?: [])
            ->replyTo($email->reply_to ?: [])
            ->subject($email->subject)
            ->from($email->from['address'], $email->from['name'])
            ->html($email->body);

        foreach ($email->attachments as $dbAttachment) {
            $attachment = match (true) {
                isset($dbAttachment['disk']) => Attachment::fromStorageDisk(
                    $dbAttachment['disk'],
                    $dbAttachment['path']
                ),
                default => Attachment::fromPath($dbAttachment['path']),
            };

            if (! empty($dbAttachment['mime'])) {
                $attachment->withMime($dbAttachment['mime']);
            }

            if (! empty($dbAttachment['as'])) {
                $attachment->as($dbAttachment['as']);
            }

            $message->attach($attachment);
        }
    }
}
