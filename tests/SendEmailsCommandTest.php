<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Stackkit\LaravelDatabaseEmails\Store;

class SendEmailsCommandTest extends TestCase
{
    /** @test */
    public function an_email_should_be_marked_as_sent()
    {
        $email = $this->sendEmail();

        $this->artisan('email:send');

        $this->assertNotNull($email->fresh()->getSendDate());
    }

    /** @test */
    public function the_number_of_attempts_should_be_incremented()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, $email->fresh()->getAttempts());

        $this->artisan('email:send');

        $this->assertEquals(1, $email->fresh()->getAttempts());
    }

    /** @test */
    public function an_email_should_not_be_sent_once_it_is_marked_as_sent()
    {
        $email = $this->sendEmail();

        $this->artisan('email:send');

        $this->assertNotNull($firstSend = $email->fresh()->getSendDate());

        sleep(1);

        $this->artisan('email:send');

        $this->assertEquals(1, $email->fresh()->getAttempts());
        $this->assertEquals($firstSend, $email->fresh()->getSendDate());
    }

    /** @test */
    public function if_an_email_fails_to_be_sent_it_should_be_logged_in_the_database()
    {
        $this->app['config']['mail.driver'] = 'does-not-exist';

        $email = $this->sendEmail();

        $this->artisan('email:send');

        $this->assertTrue($email->fresh()->hasFailed());
        $this->assertStringContains('Driver [does-not-exist] not supported.', $email->fresh()->getError());
    }

    /** @test */
    public function the_number_of_emails_sent_per_minute_should_be_limited()
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->sendEmail();
        }

        $this->app['config']['laravel-database-emails.limit'] = 25;

        $this->artisan('email:send');

        $this->assertEquals(5, DB::table('emails')->whereNull('sent_at')->count());
    }

    /** @test */
    public function an_email_should_never_be_sent_before_its_scheduled_date()
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
    public function emails_will_be_sent_until_max_try_count_has_been_reached()
    {
        $this->app['config']['mail.driver'] = 'does-not-exist';

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
    public function the_failed_status_and_error_is_cleared_if_a_previously_failed_email_is_sent_succesfully()
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
}
