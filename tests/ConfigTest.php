<?php

namespace Tests;

use Stackkit\LaravelDatabaseEmails\Config;

class ConfigTest extends TestCase
{
    /** @test */
    public function test_max_attempt_count()
    {
        $this->assertEquals(3, Config::maxAttemptCount());

        $this->app['config']->set('laravel-database-emails.attempts', 5);

        $this->assertEquals(5, Config::maxAttemptCount());
    }

    /** @test */
    public function test_encrypt_emails()
    {
        $this->assertFalse(Config::encryptEmails());

        $this->app['config']->set('laravel-database-emails.encrypt', true);

        $this->assertTrue(Config::encryptEmails());
    }

    /** @test */
    public function test_testing()
    {
        $this->assertFalse(Config::testing());

        $this->app['config']->set('laravel-database-emails.testing.enabled', true);

        $this->assertTrue(Config::testing());
    }

    /** @test */
    public function test_test_email_address()
    {
        $this->assertEquals('test@email.com', Config::testEmailAddress());

        $this->app['config']->set('laravel-database-emails.testing.email', 'test+update@email.com');

        $this->assertEquals('test+update@email.com', Config::testEmailAddress());
    }

    /** @test */
    public function test_cronjob_email_limit()
    {
        $this->assertEquals(20, Config::cronjobEmailLimit());

        $this->app['config']->set('laravel-database-emails.limit', 15);

        $this->assertEquals(15, Config::cronjobEmailLimit());
    }
}
