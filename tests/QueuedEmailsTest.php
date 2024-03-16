<?php

namespace Tests;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Stackkit\LaravelDatabaseEmails\SendEmailJob;
use Illuminate\Support\Facades\Mail;

class QueuedEmailsTest extends TestCase
{
    #[Test]
    public function queueing_an_email_will_leave_sending_on_false()
    {
        Queue::fake();

        $email = $this->queueEmail();

        $this->assertEquals(0, $email->sending);
    }

    #[Test]
    public function queueing_an_email_will_set_the_queued_at_column()
    {
        Queue::fake();

        $email = $this->queueEmail();

        $this->assertNotNull($email->queued_at);
    }

    #[Test]
    public function queueing_an_email_will_dispatch_a_job()
    {
        Queue::fake();

        $email = $this->queueEmail();

        Queue::assertPushed(SendEmailJob::class, function (SendEmailJob $job) use ($email) {
            return $job->email->id === $email->id;
        });
    }

    #[Test]
    public function emails_can_be_queued_on_a_specific_connection()
    {
        Queue::fake();

        $this->queueEmail('some-connection');

        Queue::assertPushed(SendEmailJob::class, function (SendEmailJob $job) {
            return $job->connection === 'some-connection';
        });
    }

    #[Test]
    public function emails_can_be_queued_on_a_specific_queue()
    {
        Queue::fake();

        $this->queueEmail('default', 'some-queue');

        Queue::assertPushed(SendEmailJob::class, function (SendEmailJob $job) {
            return $job->queue === 'some-queue';
        });
    }

    #[Test]
    public function emails_can_be_queued_with_a_delay()
    {
        Queue::fake();

        $delay = now()->addMinutes(6);

        $this->queueEmail(null, null, $delay);

        Queue::assertPushed(SendEmailJob::class, function (SendEmailJob $job) use ($delay) {
            return $job->delay->getTimestamp() === $delay->timestamp;
        });
    }

    #[Test]
    public function the_send_email_job_will_call_send_on_the_email_instance()
    {
        Queue::fake();

        $email = $this->queueEmail('default', 'some-queue');

        $job = new SendEmailJob($email);

        Mail::shouldReceive('send')->once();

        $job->handle();
    }

    #[Test]
    public function the_mail_will_be_marked_as_sent_when_job_is_finished()
    {
        Queue::fake();

        $email = $this->queueEmail('default', 'some-queue');

        $job = new SendEmailJob($email);
        $job->handle();

        $this->assertTrue($email->isSent());
    }
}
