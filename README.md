<p align="center">
<img src="https://github.com/stackkit/laravel-database-emails/workflows/Run%20tests/badge.svg?branch=master" alt="Build Status">
<a href="https://packagist.org/packages/stackkit/laravel-database-emails"><img src="https://poser.pugx.org/stackkit/laravel-database-emails/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/stackkit/laravel-database-emails"><img src="https://poser.pugx.org/stackkit/laravel-database-emails/license.svg" alt="License"></a>
</p>

# Introduction

This package allows you to store and send e-mails using a database. 

# Requirements

This package requires Laravel 10 or 11.

# Installation

Require the package using composer.

```bash
composer require stackkit/laravel-database-emails
```

Publish the configuration files.

```bash
php artisan vendor:publish --tag=database-emails-config
php artisan vendor:publish --tag=database-emails-migrations
```

Create the database table required for this package.

```bash
php artisan migrate
```

Add the e-mail cronjob to your scheduler

```php
<?php

/**
 * Define the application's command schedule.
 *
 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
 * @return void
 */
protected function schedule(Schedule $schedule)
{
     $schedule->command('email:send')->everyMinute()->withoutOverlapping(5);
}
```


# Usage

### Send an email

E-mails are composed the same way mailables are created.

```php
<?php

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
<?php
Email::compose()
    ->user($user)
    ->send();
```

By default, the `name` column will be used to set the recipient's name. If you wish to use another column, you should implement the `preferredEmailName` method in your model.

```php
<?php

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function preferredEmailName(): string
    {
        return $this->first_name;
    }
}
```

By default, the `email` column will be used to set the recipient's e-mail address. If you wish to use another column, you should implement the `preferredEmailAddress` method in your model.

```php
<?php

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function preferredEmailAddress(): string
    {
        return $this->work_email;
    }
}
```

By default, the app locale will be used to set the recipient's locale. If you wish to use another column, you should implement the `preferredEmailLocale` method in your model.

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Translation\HasLocalePreference;

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
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->mailable(new OrderShipped())
    ->send();
```

### Attachments

To start attaching files to your e-mails, you may use the `attach` method like you normally would in Laravel.
However, you will have to use this package's `Attachment` class.


```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;
use Stackkit\LaravelDatabaseEmails\Attachment;

Email::compose()
    ->attachments([
        Attachment::fromPath(__DIR__.'/files/pdf-sample.pdf'),
        Attachment::fromPath(__DIR__.'/files/my-file.txt')->as('Test123 file'),
        Attachment::fromStorageDisk('my-custom-disk', 'test.txt'),
    ])
    ->send();
```

<small>
Note: `fromData()` and `fromStorage()` are not supported as the work with raw data.
</small>

### Attaching models to e-mails

You may attach models to your e-mails.

```php

Email::compose()
    ->model(User::find(1));

```

### Scheduling

You may schedule an e-mail by calling `later` instead of `send`. You must provide a Carbon instance or a strtotime valid date.

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->later('+2 hours');
```

### Queueing e-mails

**Important**: When queueing mail using the `queue` function, it is no longer necessary to schedule the `email:send` command. Please make sure it is removed from `app/Console/Kernel.php`.

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->queue();

// on a specific connection
Email::compose()
    ->queue('sqs');

// on a specific queue
Email::compose()
    ->queue(null, 'email-queue');

// timeout (send mail in 10 minutes)
Email::compose()
    ->queue(null, null, now()->addMinutes(10));
```

If you need more flexibility over how to queued mails are retried, please implement your own email job.

Within the job you can send the mail like this:

```php
use Stackkit\LaravelDatabaseEmails\Sender;

(new Sender)->send($email);
```

### Test mode

When enabled, all newly created e-mails will be sent to the specified test e-mail address. This is turned off by default.

```
DATABASE_EMAILS_TESTING_ENABLED=true
DATABASE_EMAILS_TESTING_EMAIL=your-test-recipient@example.com
```

### E-mails to send per minute

To configure how many e-mails should be sent each command.

```
DATABASE_EMAILS_LIMIT=20
```

### Send e-mails immediately

Useful during development when Laravel Scheduler is not running

To enable, set the following environment variable:

```
DATABASE_EMAILS_IMMEDIATELY=true
```

### Pruning models

```php
use Stackkit\LaravelDatabaseEmails\Email;

$schedule->command('model:prune', [
    '--model' => [Email::class],
])->everyMinute();
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
