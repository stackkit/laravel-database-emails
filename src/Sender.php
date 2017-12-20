<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;

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

        $this->getMailerInstance()->send([], [], function (Message $message) use ($email) {
            $this->buildMessage($message, $email);
        });

        $email->markAsSent();
    }

    /**
     * Get the instance of the Laravel mailer.
     *
     * @return Mailer
     */
    private function getMailerInstance()
    {
        return app('mailer');
    }

    /**
     * Build the e-mail message.
     *
     * @param  Message $message
     * @param  Email   $email
     */
    private function buildMessage(Message $message, Email $email)
    {

        $from = $email->hasFrom() ? $email->getFrom() : ['address' => config('mail.from.address'), 'name' => config('mail.from.name')];
        foreach ($from as $key => $val) {
            $from[$key] = !empty($val) ? $val : config('mail.from.' . $key);
        }

        $message->to($email->getRecipient())
            ->cc($email->hasCc() ? $email->getCc() : [])
            ->bcc($email->hasBcc() ? $email->getBcc() : [])
            ->subject($email->getSubject())
            ->from($from['address'], $from['name'])
            ->setBody($email->getBody(), 'text/html');

        $attachmentMap = [
            'attachment'    => 'attach',
            'rawAttachment' => 'attachData',
        ];

        foreach ((array)$email->getAttachments() as $attachment) {
            call_user_func_array([$message, $attachmentMap[$attachment['type']]], $attachment['attachment']);
        }
    }
}
