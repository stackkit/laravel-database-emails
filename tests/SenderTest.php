<?php

namespace Tests;

use Swift_Events_SendEvent;
use Illuminate\Support\Facades\Mail;
use Stackkit\LaravelDatabaseEmails\Email;
use TCPDF;

class SenderTest extends TestCase
{
    /** @var Swift_Events_SendEvent[] */
    public $sent = [];

    public function setUp(): void
    {
        parent::setUp();

        Mail::getSwiftMailer()->registerPlugin(new TestingMailEventListener($this));
    }

    /** @test */
    public function it_sends_an_email()
    {
        $this->sendEmail();

        Mail::shouldReceive('send')
            ->once();

        $this->artisan('email:send');
    }

    /** @test */
    public function the_email_has_a_correct_from_email_and_from_name()
    {
        $this->app['config']->set('mail.from.address', 'testfromaddress@gmail.com');
        $this->app['config']->set('mail.from.name', 'From CI test');

        $this->sendEmail();

        $this->artisan('email:send');

        $from = reset($this->sent)->getMessage()->getFrom();

        $this->assertEquals('testfromaddress@gmail.com', key($from));
        $this->assertEquals('From CI test', $from[key($from)]);

        // custom from...
        $this->sent = [];

        $this->composeEmail()->from('marick@dolphiq.nl', 'Marick')->send();
        $this->artisan('email:send');
        $from = reset($this->sent)->getMessage()->getFrom();
        $this->assertEquals('marick@dolphiq.nl', key($from));
        $this->assertEquals('Marick', $from[key($from)]);

        // only address
        $this->sent = [];
        $this->composeEmail()->from('marick@dolphiq.nl')->send();
        $this->artisan('email:send');
        $from = reset($this->sent)->getMessage()->getFrom();
        $this->assertEquals('marick@dolphiq.nl', key($from));
        $this->assertEquals(config('mail.from.name'), $from[key($from)]);

        // only name
        $this->sent = [];
        $this->composeEmail()->from(null, 'Marick')->send();
        $this->artisan('email:send');
        $from = reset($this->sent)->getMessage()->getFrom();
        $this->assertEquals(config('mail.from.address'), key($from));
        $this->assertEquals('Marick', $from[key($from)]);
    }

    /** @test */
    public function it_sends_emails_to_the_correct_recipients()
    {
        $this->sendEmail(['recipient' => 'john@doe.com']);
        $this->artisan('email:send');
        $to = reset($this->sent)->getMessage()->getTo();
        $this->assertCount(1, $to);
        $this->assertArrayHasKey('john@doe.com', $to);

        $this->sent = [];
        $this->sendEmail(['recipient' => ['john@doe.com', 'john+2@doe.com']]);
        $this->artisan('email:send');
        $to = reset($this->sent)->getMessage()->getTo();
        $this->assertCount(2, $to);
        $this->assertArrayHasKey('john@doe.com', $to);
        $this->assertArrayHasKey('john+2@doe.com', $to);
    }

    /** @test */
    public function it_adds_the_cc_addresses()
    {
        $this->sendEmail(['cc' => 'cc@test.com']);
        $this->artisan('email:send');
        $cc = reset($this->sent)->getMessage()->getCc();
        $this->assertCount(1, $cc);
        $this->assertArrayHasKey('cc@test.com', $cc);

        $this->sent = [];
        $this->sendEmail(['cc' => ['cc@test.com', 'cc+2@test.com']]);
        $this->artisan('email:send');
        $cc = reset($this->sent)->getMessage()->getCc();
        $this->assertCount(2, $cc);
        $this->assertArrayHasKey('cc@test.com', $cc);
        $this->assertArrayHasKey('cc+2@test.com', $cc);
    }

    /** @test */
    public function it_adds_the_bcc_addresses()
    {
        $this->sendEmail(['bcc' => 'bcc@test.com']);
        $this->artisan('email:send');
        $bcc = reset($this->sent)->getMessage()->getBcc();
        $this->assertCount(1, $bcc);
        $this->assertArrayHasKey('bcc@test.com', $bcc);

        $this->sent = [];
        $this->sendEmail(['bcc' => ['bcc@test.com', 'bcc+2@test.com']]);
        $this->artisan('email:send');
        $bcc = reset($this->sent)->getMessage()->getBcc();
        $this->assertCount(2, $bcc);
        $this->assertArrayHasKey('bcc@test.com', $bcc);
        $this->assertArrayHasKey('bcc+2@test.com', $bcc);
    }

    /** @test */
    public function the_email_has_the_correct_subject()
    {
        $this->sendEmail(['subject' => 'Hello World']);

        $this->artisan('email:send');

        $subject = reset($this->sent)->getMessage()->getSubject();

        $this->assertEquals('Hello World', $subject);
    }

    /** @test */
    public function the_email_has_the_correct_body()
    {
        $this->sendEmail(['variables' => ['name' => 'John Doe']]);
        $this->artisan('email:send');
        $body = reset($this->sent)->getMessage()->getBody();
        $this->assertEquals(view('tests::dummy', ['name' => 'John Doe']), $body);

        $this->sent = [];
        $this->sendEmail(['variables' => []]);
        $this->artisan('email:send');
        $body = reset($this->sent)->getMessage()->getBody();
        $this->assertEquals(view('tests::dummy'), $body);
    }

    /** @test */
    public function attachments_are_added_to_the_email()
    {
        $this->composeEmail()
            ->attach(__DIR__ . '/files/pdf-sample.pdf')
            ->send();
        $this->artisan('email:send');

        $attachments = reset($this->sent)->getMessage()->getChildren();
        $attachment = reset($attachments);

        $this->assertCount(1, $attachments);
        $this->assertEquals('attachment; filename=pdf-sample.pdf', $attachment->getHeaders()->get('content-disposition')->getFieldBody());
        $this->assertEquals('application/pdf', $attachment->getContentType());
    }

    /** @test */
    public function attachments_are_not_added_if_the_data_is_not_valid()
    {
        $this->sent = [];
        $this->composeEmail()->attach(null)->send();
        $this->artisan('email:send');
        $attachments = reset($this->sent)->getMessage()->getChildren();
        $this->assertCount(0, $attachments);

        $this->sent = [];
        $this->composeEmail()->attach(false)->send();
        $this->artisan('email:send');
        $attachments = reset($this->sent)->getMessage()->getChildren();
        $this->assertCount(0, $attachments);

        $this->sent = [];
        $this->composeEmail()->attach('')->send();
        $this->artisan('email:send');
        $attachments = reset($this->sent)->getMessage()->getChildren();
        $this->assertCount(0, $attachments);
    }

    /** @test */
    public function raw_attachments_are_added_to_the_email()
    {
        $pdf = new TCPDF;
        $pdf->Write(0, 'Hello CI!');

        $rawData = $pdf->Output('generated.pdf', 'S');

        $this->composeEmail()
            ->attachData($rawData, 'hello-ci.pdf', [
                'mime' => 'application/pdf',
            ])
            ->send();
        $this->artisan('email:send');

        $attachments = reset($this->sent)->getMessage()->getChildren();
        $attachment = reset($attachments);

        $this->assertCount(1, $attachments);
        $this->assertEquals('attachment; filename=hello-ci.pdf', $attachment->getHeaders()->get('content-disposition')->getFieldBody());
        $this->assertEquals('application/pdf', $attachment->getContentType());
        $this->assertTrue(md5($attachment->getBody()) == md5($rawData));
    }

    /** @test */
    public function old_json_encoded_attachments_can_still_be_read()
    {
        $email = $this->sendEmail();
        $email->attachments = json_encode([1, 2, 3]);
        $email->save();

        $this->assertEquals([1, 2, 3], $email->fresh()->getAttachments());

        $email->attachments = serialize([4, 5, 6]);
        $email->save();

        $this->assertEquals([4, 5, 6], $email->fresh()->getAttachments());
    }

    /** @test */
    public function emails_can_be_sent_immediately()
    {
        $this->app['config']->set('laravel-database-emails.immediately', false);
        $this->sendEmail();
        $this->assertCount(0, $this->sent);
        Email::truncate();

        $this->app['config']->set('laravel-database-emails.immediately', true);
        $this->sendEmail();
        $this->assertCount(1, $this->sent);

        $this->artisan('email:send');
        $this->assertCount(1, $this->sent);
    }

    /** @test */
    public function raw_attachments_are_not_added_if_the_data_is_not_valid()
    {
        $this->sent = [];
        $this->composeEmail()->attachData(null, 'test.png')->send();
        $this->artisan('email:send');
        $attachments = reset($this->sent)->getMessage()->getChildren();
        $this->assertCount(0, $attachments);

        $this->sent = [];
        $this->composeEmail()->attachData(false, 'test.png')->send();
        $this->artisan('email:send');
        $attachments = reset($this->sent)->getMessage()->getChildren();
        $this->assertCount(0, $attachments);

        $this->sent = [];
        $this->composeEmail()->attachData('', 'test.png')->send();
        $this->artisan('email:send');
        $attachments = reset($this->sent)->getMessage()->getChildren();
        $this->assertCount(0, $attachments);
    }
}
