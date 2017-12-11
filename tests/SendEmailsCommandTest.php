<?php

namespace Tests;

use Buildcode\LaravelDatabaseEmails\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Swift_Events_SendEvent;

class SendEmailsCommandTest extends TestCase
{
    /** @var Swift_Events_SendEvent[] */
    public $sent = [];

    function setUp()
    {
        parent::setUp();

        Mail::getSwiftMailer()->registerPlugin(new TestingMailEventListener($this));
    }

    /** @test */
    function an_email_should_be_marked_as_sent()
    {
        $email = $this->sendEmail();

        $this->artisan('email:send');

        $this->assertNotNull($email->fresh()->getSendDate());
    }

    /** @test */
    function the_number_of_attempts_should_be_incremented()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, $email->fresh()->getAttempts());

        $this->artisan('email:send');

        $this->assertEquals(1, $email->fresh()->getAttempts());
    }

    /** @test */
    function an_email_should_not_be_sent_once_it_is_marked_as_sent()
    {
        $email = $this->sendEmail();

        $this->artisan('email:send');

        $this->assertEquals($firstSend = date('Y-m-d H:i:s'), $email->fresh()->getSendDate());

        $this->artisan('email:send');

        $this->assertEquals(1, $email->fresh()->getAttempts());
        $this->assertEquals($firstSend, $email->fresh()->getSendDate());
    }

    /** @test */
    function an_email_should_be_locked_so_overlapping_cronjobs_cannot_send_an_already_processing_email()
    {
        $email = $this->sendEmail();

        Event::listen('before.send', function () {
            $this->artisan('email:send');
        });

        $this->artisan('email:send');

        $this->assertEquals(1, $email->fresh()->getAttempts());
    }

    /** @test */
    function if_an_email_fails_to_be_sent_it_should_be_logged_in_the_database()
    {
        $email = $this->sendEmail();

        Event::listen('before.send', function () {
            throw new \Exception('Simulating some random error');
        });

        $this->artisan('email:send');

        $this->assertTrue($email->fresh()->hasFailed());
        $this->assertContains('Simulating some random error', $email->fresh()->getError());
    }

    /** @test */
    function the_number_of_emails_sent_per_minute_should_be_limited()
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->sendEmail();
        }

        $this->app['config']['laravel-database-emails.limit'] = 25;

        $this->artisan('email:send');

        $this->assertEquals(5, DB::table('emails')->whereNull('sent_at')->count());
    }

    /** @test */
    function an_email_should_never_be_sent_before_its_scheduled_date()
    {
        $email = $this->scheduleEmail(Carbon::now()->addHour(1));
        $this->artisan('email:send');
        $email = $email->fresh();
        $this->assertEquals(0, $email->getAttempts());
        $this->assertNull($email->getSendDate());


        $email->update(['scheduled_at' => Carbon::now()->toDateTimeString()]);
        $this->artisan('email:send');
        $email = $email->fresh();
        $this->assertEquals(1, $email->getAttempts());
        $this->assertNotNull($email->getSendDate());
    }

    /** @test */
    function emails_will_be_sent_until_max_try_count_has_been_reached()
    {
        Event::listen('before.send', function () {
            throw new \Exception('Simulating some random error');
        });

        $this->sendEmail();
        $this->assertCount(1, (new Store)->getQueue());
        $this->artisan('email:send');
        $this->assertCount(1, (new Store)->getQueue());
        $this->artisan('email:send');
        $this->assertCount(1, (new Store)->getQueue());
        $this->artisan('email:send');
        $this->assertCount(0, (new Store)->getQueue());
    }

    /** @test */
    function the_failed_status_and_error_is_cleared_if_a_previously_failed_email_is_sent_succesfully()
    {
        $email = $this->sendEmail();

        $email->update([
            'failed'   => true,
            'error'    => 'Simulating some random error',
            'attempts' => 1,
        ]);

        $this->assertTrue($email->fresh()->hasFailed());
        $this->assertEquals('Simulating some random error', $email->fresh()->getError());

        $this->artisan('email:send');

        $this->assertFalse($email->fresh()->hasFailed());
        $this->assertEmpty($email->fresh()->getError());
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
}
