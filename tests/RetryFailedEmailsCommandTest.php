<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class RetryFailedEmailsCommandTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app['config']['laravel-database-emails.attempts'] = 3;
    }

    /** @test */
    public function an_email_cannot_be_reset_if_the_max_attempt_count_has_not_been_reached()
    {
        $this->app['config']['mail.driver'] = 'does-not-exist';

        $this->sendEmail();

        $this->artisan('email:send');

        $this->assertEquals(1, DB::table('emails')->count());

        $this->artisan('email:resend');

        $this->assertEquals(1, DB::table('emails')->count());

        // try 2 more times, reaching 3 attempts and thus failing and able to retry
        $this->artisan('email:send');
        $this->artisan('email:send');
        $this->artisan('email:resend');

        $this->assertEquals(2, DB::table('emails')->count());
    }

    /** @test */
    public function a_single_email_can_be_resent()
    {
        $emailA = $this->sendEmail();
        $emailB = $this->sendEmail();

        // simulate emailB being failed...
        $emailB->update(['failed' => 1, 'attempts' => 3]);

        $this->artisan('email:resend', ['id' => 2]);

        $this->assertEquals(3, DB::table('emails')->count());
    }
}
