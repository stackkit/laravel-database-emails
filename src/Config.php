<?php

namespace Stackkit\LaravelDatabaseEmails;

class Config
{
    /**
     * Get the maximum number of times an e-mail may be attempted to be sent.
     *
     * @return int
     */
    public static function maxAttemptCount()
    {
        return max(config('laravel-database-emails.attempts', 1), 3);
    }

    /**
     * Determine if newly created e-mails should be encrypted.
     *
     * @return bool
     */
    public static function encryptEmails()
    {
        return config('laravel-database-emails.encrypt', false);
    }

    /**
     * Determine if newly created e-mails should be sent to the test e-mail address.
     *
     * @return bool
     */
    public static function testing()
    {
        return (bool) config('laravel-database-emails.testing.enabled', false);
    }

    /**
     * Get the test e-mail address.
     *
     * @return string
     */
    public static function testEmailAddress()
    {
        return config('laravel-database-emails.testing.email');
    }

    /**
     * Get the number of e-mails the cronjob may send at a time.
     *
     * @return int
     */
    public static function cronjobEmailLimit()
    {
        return config('laravel-database-emails.limit', 20);
    }

    /**
     * Determine if e-mails should be sent immediately.
     *
     * @return bool
     */
    public static function sendImmediately()
    {
        return (bool) config('laravel-database-emails.immediately', false);
    }
}
