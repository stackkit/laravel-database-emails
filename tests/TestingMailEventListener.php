<?php

namespace Tests;

use Swift_Events_EventListener;

class TestingMailEventListener implements Swift_Events_EventListener
{
    /** @var SendEmailsCommandTest */
    private $test;

    public function __construct(TestCase $test)
    {
        $this->test = $test;
    }

    public function beforeSendPerformed($event)
    {
        $this->test->sent[] = $event;
    }
}
