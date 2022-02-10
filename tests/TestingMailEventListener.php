<?php

namespace Tests;

use Stackkit\LaravelDatabaseEmails\SentMessage;
use Swift_Events_EventListener;

class TestingMailEventListener implements Swift_Events_EventListener
{
    /** @var SendEmailsCommandTest */
    private $test;

    public function __construct(TestCase $test)
    {
        $this->test = $test;
    }

    public function beforeSendPerformed(\Swift_Events_Event $event)
    {
        $this->test->sent[] = SentMessage::createFromSwiftMailer($event->getMessage());
    }
}
