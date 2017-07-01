<?php

namespace Tests;

use Buildcode\LaravelDatabaseEmails\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class RetryFailedEmailsCommandTest extends TestCase
{
    /** @test */
    function it_should_retry_sending_failed_emails()
    {
        Event::listen('before.send', function () {
            throw new \Exception('Simulating some random error');
        });

        $email = $this->sendEmail();

        $this->artisan('email:send');

        $email = $email->fresh();

        $this->assertTrue($email->fresh()->hasFailed());
        $this->assertEquals(1, DB::table('emails')->count());

        $this->artisan('email:retry');

        $this->assertEquals(2, DB::table('emails')->count());
        $this->assertEquals(1, (new Store())->getQueue()->count());
    }

    /** @test */
    function a_single_email_can_be_resent()
    {
        Event::listen('before.send', function () {
            throw new \Exception('Simulating some random error');
        });

        $this->sendEmail();
        $this->sendEmail();

        $this->artisan('email:send');

        $this->assertEquals(2, DB::table('emails')->count());

        $this->artisan('email:retry', ['id' => 1]);

        $this->assertEquals(3, DB::table('emails')->count());
    }
}