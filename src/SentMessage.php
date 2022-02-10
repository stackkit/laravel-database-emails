<?php

namespace Stackkit\LaravelDatabaseEmails;

use Swift_Mime_SimpleMimeEntity;
use Symfony\Component\Mime\Part\DataPart;

class SentMessage
{
    public $from = [];
    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $subject = '';
    public $body = '';
    public $attachments = [];
    public $headers = [];

    public static function createFromSymfonyMailer(\Symfony\Component\Mime\Email $email)
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

    public static function createFromSwiftMailer(\Swift_Mime_SimpleMessage $message)
    {
        $sentMessage = new self();

        $sentMessage->from = $message->getFrom();
        $sentMessage->to = $message->getTo();
        $sentMessage->cc = $message->getCc();
        $sentMessage->bcc = $message->getBcc();
        $sentMessage->subject = $message->getSubject();
        $sentMessage->body = $message->getBody();
        $sentMessage->attachments = array_map(function(Swift_Mime_SimpleMimeEntity $entity) {
            return [
                'body' => $entity->getBody(),
                'disposition' => $entity->getContentType() . ' ' . $entity->getHeaders()->get('content-disposition'),
            ];
        }, $message->getChildren());

        return $sentMessage;
    }
}
