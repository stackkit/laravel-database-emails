<?php

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Mail\Message;
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

        $sentMessage = Mail::send([], [], function (Message $message) use ($email) {
            $this->buildMessage($message, $email);
        });

        // This is used so we can assert things on the sent message in Laravel 9+ since we cannot use
        // the Swift Mailer plugin anymore. So this is purely used for in the PHPUnit tests.
        if (version_compare(app()->version(), '9.0.0', '>=') && !is_null($sentMessage)) {
            event(new MessageSent($sentMessage));
        }

        $email->markAsSent();
    }

    /**
     * Build the e-mail message.
     *
     * @param  Message $message
     * @param  Email   $email
     */
    private function buildMessage(Message $message, Email $email)
    {
        $message->to($email->getRecipient())
            ->cc($email->hasCc() ? $email->getCc() : [])
            ->bcc($email->hasBcc() ? $email->getBcc() : [])
            ->subject($email->getSubject())
            ->from($email->getFromAddress(), $email->getFromName());

        if (version_compare(app()->version(), '9.0.0', '>=')) {
            // Symfony Mailer
            $message->html($email->getBody());
        } else {
            // SwiftMail
            $message->setBody($email->getBody(), 'text/html');
        }

        $attachmentMap = [
            'attachment'    => 'attach',
            'rawAttachment' => 'attachData',
        ];

        foreach ((array) $email->getAttachments() as $attachment) {
            call_user_func_array([$message, $attachmentMap[$attachment['type']]], $attachment['attachment']);
        }
    }
}
