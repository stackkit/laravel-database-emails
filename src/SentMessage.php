<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class SentMessage
{
    public $from = [];
    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $replyTo = [];
    public $subject = '';
    public $body = '';
    public $attachments = [];
    public $headers = [];

    public static function createFromSymfonyMailer(Email $email): SentMessage
    {
        $sentMessage = new self();

        foreach ($email->getFrom() as $address) {
            $sentMessage->from[$address->getAddress()] = $address->getName();
        }

        foreach ($email->getTo() as $address) {
            $sentMessage->to[$address->getAddress()] = $address->getName();
        }

        foreach ($email->getCc() as $address) {
            $sentMessage->cc[$address->getAddress()] = $address->getName();
        }

        foreach ($email->getBcc() as $address) {
            $sentMessage->bcc[$address->getAddress()] = $address->getName();
        }

        foreach ($email->getReplyTo() as $address) {
            $sentMessage->replyTo[$address->getAddress()] = $address->getName();
        }

        $sentMessage->subject = $email->getSubject();
        $sentMessage->body = $email->getHtmlBody();
        $sentMessage->attachments = array_map(function (DataPart $dataPart) {
            return [
                'body' => $dataPart->getBody(),
                'disposition' => $dataPart->asDebugString(),
            ];
        }, $email->getAttachments());

        return $sentMessage;
    }
}
