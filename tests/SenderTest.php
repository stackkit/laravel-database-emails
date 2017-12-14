<?php

namespace Tests;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\Mail;
use Swift_Events_SendEvent;

class SenderTest extends TestCase
{
    /** @var Swift_Events_SendEvent[] */
    public $sent = [];

    function setUp()
    {
        parent::setUp();

        Mail::getSwiftMailer()->registerPlugin(new TestingMailEventListener($this));
    }

    /** @test */
    function it_sends_an_email()
    {
        $this->sendEmail();

        Mail::shouldReceive('send')
            ->once();

        $this->artisan('email:send');
    }

    /** @test */
    function the_email_has_a_correct_from_email_and_from_name()
    {
        $this->app['config']->set('mail.from.address', 'testfromaddress@gmail.com');
        $this->app['config']->set('mail.from.name', 'From CI test');

        $this->sendEmail();

        $this->artisan('email:send');

        $from = reset($this->sent)->getMessage()->getFrom();

        $this->assertEquals('testfromaddress@gmail.com', key($from));
        $this->assertEquals('From CI test', $from[key($from)]);
    }

    /** @test */
    function it_sends_emails_to_the_correct_recipients()
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
    function it_adds_the_cc_addresses()
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
    function it_adds_the_bcc_addresses()
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
    function the_email_has_the_correct_subject()
    {
        $this->sendEmail(['subject' => 'Hello World']);

        $this->artisan('email:send');

        $subject = reset($this->sent)->getMessage()->getSubject();

        $this->assertEquals('Hello World', $subject);
    }

    /** @test */
    function the_email_has_the_correct_body()
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
    function attachments_are_added_to_the_email()
    {
        $this->composeEmail()
            ->attach(__DIR__ . '/files/pdf-sample.pdf')
            ->send();
        $this->artisan('email:send');

        $attachments = reset($this->sent)->getMessage()->getChildren();
        $attachment = reset($attachments);

        $this->assertCount(1, $attachments);
        $this->assertEquals('attachment; filename=pdf-sample.pdf',$attachment->getHeaders()->get('content-disposition')->getFieldBody());
        $this->assertEquals('application/pdf', $attachment->getContentType());
    }

    /** @test */
    function raw_attachments_are_added_to_the_email()
    {
        $pdf = new Dompdf;
        $pdf->loadHtml('Hello CI!');
        $pdf->setPaper('A4');

        $this->composeEmail()
            ->attachData($pdf->outputHtml(), 'hello-ci.pdf', [
                'mime' => 'application/pdf'
            ])
            ->send();
        $this->artisan('email:send');

        $attachments = reset($this->sent)->getMessage()->getChildren();
        $attachment = reset($attachments);

        $this->assertCount(1, $attachments);
        $this->assertEquals('attachment; filename=hello-ci.pdf',$attachment->getHeaders()->get('content-disposition')->getFieldBody());
        $this->assertEquals('application/pdf', $attachment->getContentType());
        $this->assertContains('Hello CI!', $attachment->getBody());
    }
}
