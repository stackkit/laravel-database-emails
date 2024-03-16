<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

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
        $message->to($email->getRecipient())
            ->cc($email->hasCc() ? $email->getCc() : [])
            ->bcc($email->hasBcc() ? $email->getBcc() : [])
            ->replyTo($email->hasReplyTo() ? $email->getReplyTo() : [])
            ->subject($email->getSubject())
            ->from($email->getFromAddress(), $email->getFromName());

        $message->html($email->getBody());

        $attachmentMap = [
            'attachment' => 'attach',
            'rawAttachment' => 'attachData',
        ];

        foreach ($email->getAttachments() as $attachment) {
            call_user_func_array([$message, $attachmentMap[$attachment['type']]], $attachment['attachment']);
        }
    }
}
