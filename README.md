[![Run tests](https://github.com/stackkit/laravel-database-emails/actions/workflows/run-tests.yml/badge.svg)](https://github.com/stackkit/laravel-database-emails/actions/workflows/run-tests.yml)
[![Latest Version on Packagist](https://poser.pugx.org/stackkit/laravel-database-emails/v/stable.svg)](https://packagist.org/packages/stackkit/laravel-database-emails)
[![Total Downloads](https://poser.pugx.org/stackkit/laravel-database-emails/downloads.svg)](https://packagist.org/packages/stackkit/laravel-database-emails)

# Introduction

This package allows you to store and send e-mails using the database. 

# Requirements

This package requires Laravel 10 or 11.

# Installation

Require the package using composer.

```shell
composer require stackkit/laravel-database-emails
```

Publish the configuration files.

```shell
php artisan vendor:publish --tag=database-emails-config
php artisan vendor:publish --tag=database-emails-migrations
```

Create the database table required for this package.

```shell
php artisan migrate
```

Add the e-mail cronjob to your scheduler

```php
protected function schedule(Schedule $schedule)
{
     $schedule->command('email:send')->everyMinute()->withoutOverlapping(5);
}
```


# Usage

### Send an email

E-mails are composed the same way mailables are created.

```php
use Stackkit\LaravelDatabaseEmails\Email;
use Illuminate\Mail\Mailables\Content;
use Stackkit\LaravelDatabaseEmails\Attachment;
use Illuminate\Mail\Mailables\Envelope;

Email::compose()
    ->content(fn (Content $content) => $content
        ->view('tests::dummy')
        ->with(['name' => 'John Doe'])
    )
    ->envelope(fn (Envelope $envelope) => $envelope
        ->subject('Hello')
        ->from('johndoe@example.com', 'John Doe')
        ->to('janedoe@example.com', 'Jane Doe')
    )
    ->attachments([
        Attachment::fromStorageDisk('s3', '/invoices/john-doe/march-2024.pdf'),
    ])
    ->send();
])
```

### Sending emails to users in your application

```php
Email::compose()
    ->user($user)
    ->send();
```

By default, the `name` column will be used to set the recipient's name. If you wish to use something different, you should implement the `preferredEmailName` method in your model.

```php
class User extends Model
{
    public function preferredEmailName(): string
    {
        return $this->first_name;
    }
}
```

By default, the `email` column will be used to set the recipient's e-mail address. If you wish to use something different, you should implement the `preferredEmailAddress` method in your model.

```php
class User extends Model
{
    public function preferredEmailAddress(): string
    {
        return $this->work_email;
    }
}
```

By default, the app locale will be used. If you wish to use something different, you should implement the `preferredEmailLocale` method in your model.

```php
class User extends Model implements HasLocalePreference
{
    public function preferredLocale(): string
    {
        return $this->locale;
    }
}
```

### Using mailables

You may also pass a mailable to the e-mail composer.

```php
Email::compose()
    ->mailable(new OrderShipped())
    ->send();
```

### Attachments

To start attaching files to your e-mails, you may use the `attachments` method like you normally would in Laravel.
However, you will have to use this package's `Attachment` class.


```php
use Stackkit\LaravelDatabaseEmails\Attachment;

Email::compose()
    ->attachments([
        Attachment::fromPath(__DIR__.'/files/pdf-sample.pdf'),
        Attachment::fromPath(__DIR__.'/files/my-file.txt')->as('Test123 file'),
        Attachment::fromStorageDisk('my-custom-disk', 'test.txt'),
    ])
    ->send();
```

> [!NOTE]
> `Attachment::fromData()` and `Attachment::fromStorage()` are not supported as they work with raw data.

### Attaching models to e-mails

You may attach a model to an e-mail. This can be useful to attach a user or another model that belongs to the e-mail.

```php
Email::compose()
    ->model(User::find(1));
```

### Scheduling

You may schedule an e-mail by calling `later` instead of `send`. You must provide a Carbon instance or a strtotime valid date.

```php
Email::compose()
    ->later('+2 hours');
```

### Queueing e-mails

> [!IMPORTANT]
> When queueing mail using the `queue` function, it is no longer necessary to schedule the `email:send` command.

```php
Email::compose()->queue();

// On a specific connection
Email::compose()->queue(connection: 'sqs');

// On a specific queue
Email::compose()->queue(queue: 'email-queue');

// Delay (send mail in 10 minutes)
Email::compose()->queue(delay: now()->addMinutes(10));
```

If you need more flexibility, you may also pass your own job class:

```php
Email::compose()->queue(jobClass: CustomSendEmailJob::class);
```

It could look like this:

```php
<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Stackkit\LaravelDatabaseEmails\SendEmailJob;

class CustomSendEmailJob extends SendEmailJob implements ShouldQueue
{
    // Define custom retries, backoff, etc...
}
```

### Test mode

When enabled, all newly created e-mails will be sent to the specified test e-mail address. This is turned off by default.

```dotenv
DB_EMAILS_TESTING_ENABLED=true
DB_EMAILS_TESTING_EMAIL=your-test-recipient@example.com
```

### E-mails to send per minute

To configure how many e-mails should be sent each command.

```dotenv
DB_EMAILS_LIMIT=20
```

### Send e-mails immediately

Useful during development when Laravel Scheduler is not running

To enable, set the following environment variable:

```dotenv
DB_EMAILS_IMMEDIATELY=true
```

### Pruning models

```php
use Stackkit\LaravelDatabaseEmails\Email;

$schedule->command('model:prune', [
    '--model' => [Email::class],
])->daily();
```

By default, e-mails are pruned when they are older than 6 months.

You may change that by adding the following to the AppServiceProvider.php:

```php
use Stackkit\LaravelDatabaseEmails\Email;

public function register(): void
{
    Email::pruneWhen(function (Email $email) {
        return $email->where(...);
    });
}
```
