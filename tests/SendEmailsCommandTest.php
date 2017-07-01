<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class SendEmailsCommandTest extends TestCase
{
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
        $this->assertEquals('Simulating some random error', $email->fresh()->getError());
    }

    /** @test */
    function a_failed_email_should_not_be_sent_again()
    {
        $email = $this->sendEmail();

        Event::listen('before.send', function () {
            throw new \Exception('Simulating some random error');
        });

        $this->artisan('email:send');

        # 1 min later...
        $this->artisan('email:send');

        $this->assertEquals(1, $email->fresh()->getAttempts());
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
}