<?php

namespace Tests;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Stackkit\LaravelDatabaseEmails\Attachment;
use Stackkit\LaravelDatabaseEmails\Email;
use Stackkit\LaravelDatabaseEmails\MessageSent;
use Stackkit\LaravelDatabaseEmails\SentMessage;

class SenderTest extends TestCase
{
    /** @var array<SentMessage> */
    public $sent = [];

    public function setUp(): void
    {
        parent::setUp();

        Event::listen(MessageSent::class, function (MessageSent $event) {
            $this->sent[] = SentMessage::createFromSymfonyMailer(
                $event->message->getSymfonySentMessage()->getOriginalMessage()
            );
        });
    }

    #[Test]
    public function it_sends_an_email()
    {
        $this->sendEmail();

        Mail::shouldReceive('send')->once();

        $this->artisan('email:send');
    }

    #[Test]
    public function the_email_has_a_correct_from_email_and_from_name()
    {
        $this->app['config']->set('mail.from.address', 'testfromaddress@gmail.com');
        $this->app['config']->set('mail.from.name', 'From CI test');

        $this->sendEmail();

        $this->artisan('email:send');

        $from = reset($this->sent)->from;

        $this->assertEquals('testfromaddress@gmail.com', key($from));
        $this->assertEquals('From CI test', $from[key($from)]);

        // custom from...
        $this->sent = [];

        $this->composeEmail(['from' => new Address('marick@dolphiq.nl', 'Marick')])->send();
        $this->artisan('email:send');
        $from = reset($this->sent)->from;
        $this->assertEquals('marick@dolphiq.nl', key($from));
        $this->assertEquals('Marick', $from[key($from)]);

        // only address
        $this->sent = [];
        $this->composeEmail(['from' => 'marick@dolphiq.nl'])->send();
        $this->artisan('email:send');
        $from = reset($this->sent)->from;
        $this->assertEquals('marick@dolphiq.nl', key($from));
        $this->assertEquals('From CI test', $from[key($from)]);
    }

    #[Test]
    public function it_sends_emails_to_the_correct_recipients()
    {
        $this->sendEmail(['recipient' => 'john@doe.com']);
        $this->artisan('email:send');
        $to = reset($this->sent)->to;
        $this->assertCount(1, $to);
        $this->assertArrayHasKey('john@doe.com', $to);

        $this->sent = [];
        $this->sendEmail(['recipient' => ['john@doe.com', 'john+2@doe.com']]);
        $this->artisan('email:send');
        $to = reset($this->sent)->to;
        $this->assertCount(2, $to);
        $this->assertArrayHasKey('john@doe.com', $to);
        $this->assertArrayHasKey('john+2@doe.com', $to);
    }

    #[Test]
    public function it_adds_the_cc_addresses()
    {
        $this->sendEmail(['cc' => 'cc@test.com']);
        $this->artisan('email:send');
        $cc = reset($this->sent)->cc;
        $this->assertCount(1, $cc);
        $this->assertArrayHasKey('cc@test.com', $cc);

        $this->sent = [];
        $this->sendEmail(['cc' => ['cc@test.com', 'cc+2@test.com']]);
        $this->artisan('email:send');
        $cc = reset($this->sent)->cc;
        $this->assertCount(2, $cc);
        $this->assertArrayHasKey('cc@test.com', $cc);
        $this->assertArrayHasKey('cc+2@test.com', $cc);
    }

    #[Test]
    public function it_adds_the_bcc_addresses()
    {
        $this->sendEmail(['bcc' => 'bcc@test.com']);
        $this->artisan('email:send');
        $bcc = reset($this->sent)->bcc;
        $this->assertCount(1, $bcc);
        $this->assertArrayHasKey('bcc@test.com', $bcc);

        $this->sent = [];
        $this->sendEmail(['bcc' => ['bcc@test.com', 'bcc+2@test.com']]);
        $this->artisan('email:send');
        $bcc = reset($this->sent)->bcc;
        $this->assertCount(2, $bcc);
        $this->assertArrayHasKey('bcc@test.com', $bcc);
        $this->assertArrayHasKey('bcc+2@test.com', $bcc);
    }

    #[Test]
    public function the_email_has_the_correct_subject()
    {
        $this->sendEmail(['subject' => 'Hello World']);

        $this->artisan('email:send');

        $subject = reset($this->sent)->subject;

        $this->assertEquals('Hello World', $subject);
    }

    #[Test]
    public function the_email_has_the_correct_body()
    {
        $this->sendEmail(['variables' => ['name' => 'John Doe']]);
        $this->artisan('email:send');
        $body = reset($this->sent)->body;
        $this->assertEquals((string) view('tests::dummy', ['name' => 'John Doe']), $body);

        $this->sent = [];
        $this->sendEmail(['variables' => []]);
        $this->artisan('email:send');
        $body = reset($this->sent)->body;
        $this->assertEquals(view('tests::dummy'), $body);
    }

    #[Test]
    public function attachments_are_added_to_the_email()
    {
        $this->composeEmail()
            ->attachments([
                Attachment::fromPath(__DIR__.'/files/pdf-sample.pdf'),
                Attachment::fromPath(__DIR__.'/files/my-file.txt')->as('Test123 file'),
                Attachment::fromStorageDisk('my-custom-disk', 'test.txt'),
            ])
            ->send();
        $this->artisan('email:send');

        $attachments = reset($this->sent)->attachments;

        $this->assertCount(3, $attachments);
        $this->assertEquals('Test123'."\n", $attachments[1]['body']);
        $this->assertEquals('text/plain disposition: attachment filename: Test123 file', $attachments[1]['disposition']);
        $this->assertEquals("my file from public disk\n", $attachments[2]['body']);
    }

    #[Test]
    public function raw_attachments_are_not_added_to_the_email()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Raw attachments are not supported in the database email driver.');

        $this->composeEmail()
            ->attachments([
                Attachment::fromData(fn () => 'test', 'test.txt'),
            ])
            ->send();
    }

    #[Test]
    public function emails_can_be_sent_immediately()
    {
        $this->app['config']->set('database-emails.immediately', false);
        $this->sendEmail();
        $this->assertCount(0, $this->sent);
        Email::truncate();

        $this->app['config']->set('database-emails.immediately', true);
        $this->sendEmail();
        $this->assertCount(1, $this->sent);

        $this->artisan('email:send');
        $this->assertCount(1, $this->sent);
    }

    #[Test]
    public function it_adds_the_reply_to_addresses()
    {
        $this->sendEmail(['reply_to' => 'replyto@test.com']);
        $this->artisan('email:send');
        $replyTo = reset($this->sent)->replyTo;
        $this->assertCount(1, $replyTo);
        $this->assertArrayHasKey('replyto@test.com', $replyTo);

        $this->sent = [];
        $this->sendEmail(['reply_to' => ['replyto1@test.com', 'replyto2@test.com']]);
        $this->artisan('email:send');
        $replyTo = reset($this->sent)->replyTo;
        $this->assertCount(2, $replyTo);
        $this->assertArrayHasKey('replyto1@test.com', $replyTo);
        $this->assertArrayHasKey('replyto2@test.com', $replyTo);

        $this->sent = [];
        $this->sendEmail([
            'reply_to' => new Address('replyto@test.com', 'NoReplyTest'),
        ]);
        $this->artisan('email:send');
        $replyTo = reset($this->sent)->replyTo;
        $this->assertCount(1, $replyTo);
        $this->assertSame(['replyto@test.com' => 'NoReplyTest'], $replyTo);

        $this->sent = [];
        $this->sendEmail([
            'reply_to' => [
                new Address('replyto@test.com', 'NoReplyTest'),
                new Address('replyto2@test.com', 'NoReplyTest2'),
            ],
        ]);
        $this->artisan('email:send');
        $replyTo = reset($this->sent)->replyTo;
        $this->assertCount(2, $replyTo);
        $this->assertSame(
            [
                'replyto@test.com' => 'NoReplyTest',
                'replyto2@test.com' => 'NoReplyTest2',
            ],
            $replyTo
        );
    }
}
