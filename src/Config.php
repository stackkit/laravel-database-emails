<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

class Config
{
    /**
     * Get the maximum number of times an e-mail may be attempted to be sent.
     */
    public static function maxAttemptCount(): int
    {
        return max(config('laravel-database-emails.attempts', 1), 3);
    }

    /**
     * Determine if newly created e-mails should be sent to the test e-mail address.
     */
    public static function testing(): bool
    {
        return (bool) config('laravel-database-emails.testing.enabled', false);
    }

    /**
     * Get the test e-mail address.
     */
    public static function testEmailAddress(): string
    {
        return config('laravel-database-emails.testing.email');
    }

    /**
     * Get the number of e-mails the cronjob may send at a time.
     */
    public static function cronjobEmailLimit(): int
    {
        return config('laravel-database-emails.limit', 20);
    }

    /**
     * Determine if e-mails should be sent immediately.
     */
    public static function sendImmediately(): bool
    {
        return (bool) config('laravel-database-emails.immediately', false);
    }
}
