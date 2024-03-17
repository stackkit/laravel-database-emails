<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Stackkit\LaravelDatabaseEmails\Config;

class ConfigTest extends TestCase
{
    #[Test]
    public function test_max_attempt_count()
    {
        $this->assertEquals(3, Config::maxAttemptCount());

        $this->app['config']->set('laravel-database-emails.attempts', 5);

        $this->assertEquals(5, Config::maxAttemptCount());
    }

    #[Test]
    public function test_testing()
    {
        $this->assertFalse(Config::testing());

        $this->app['config']->set('laravel-database-emails.testing.enabled', true);

        $this->assertTrue(Config::testing());
    }

    #[Test]
    public function test_test_email_address()
    {
        $this->assertEquals('test@email.com', Config::testEmailAddress());

        $this->app['config']->set('laravel-database-emails.testing.email', 'test+update@email.com');

        $this->assertEquals('test+update@email.com', Config::testEmailAddress());
    }

    #[Test]
    public function test_cronjob_email_limit()
    {
        $this->assertEquals(20, Config::cronjobEmailLimit());

        $this->app['config']->set('laravel-database-emails.limit', 15);

        $this->assertEquals(15, Config::cronjobEmailLimit());
    }
}
