<p align="center">
  <img src="/logo.png">
</p>
<p align="center">
<img src="https://github.com/marickvantuil/laravel-database-emails/workflows/Run tests/badge.svg" alt="Build Status">
<a href="https://packagist.org/packages/stackkit/laravel-database-emails"><img src="https://poser.pugx.org/stackkit/laravel-database-emails/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/stackkit/laravel-database-emails"><img src="https://poser.pugx.org/stackkit/laravel-database-emails/license.svg" alt="License"></a>
</p>

# Package Documentation

This package allows you to store and send e-mails using a database. 

## Contribution

The package is MIT licenced, meaning it's open source and you are free to copy or fork it and modify it any way you wish.

We feel the package is currently feature complete, but feel free to send a pull request or help improve existing code.


# Installation

Require the package using composer.

```bash
composer require stackkit/laravel-database-emails
```

If you're running Laravel 5.5 or later you may skip this step. Add the service provider to your application.

```
Stackkit\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider::class,
```

Publish the configuration files.

```bash
php artisan vendor:publish --provider=Stackkit\\LaravelDatabaseEmails\\LaravelDatabaseEmailsServiceProvider
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

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->label('welcome')
    ->recipient('john@doe.com')
    ->subject('This is a test')
    ->view('emails.welcome')
    ->variables([
        'name' => 'John Doe',
    ])
    ->send();
```

### Specify multiple recipients

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->recipient([
        'john@doe.com',
        'jane@doe.com'
    ]);
```

### CC and BCC

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->cc('john@doe.com')
    ->cc(['john@doe.com', 'jane@doe.com'])
    ->bcc('john@doe.com')
    ->bcc(['john@doe.com', 'jane@doe.com']);
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

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->attach('/path/to/file');
```

Or for in-memory attachments:

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->attachData('<p>Your order has shipped!</p>', 'order.html');
```

### Custom Sender

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->from('john@doe.com', 'John Doe');
```

### Scheduling

You may schedule an e-mail by calling `later` instead of `send`. You must provide a Carbon instance or a strtotime valid date.

```php
<?php

use Stackkit\LaravelDatabaseEmails\Email;

Email::compose()
    ->later('+2 hours');
```

### Encryption (Optional)

If you wish to encrypt your e-mails, please enable the `encrypt` option in the configuration file. This is disabled by default. Encryption and decryption will be handled by Laravel's built-in encryption mechanism. Please note that by encrypting the e-mail it takes more disk space.

```text
Without encryption

7    bytes (label)
16   bytes (recipient)
20   bytes (subject)
48   bytes (view name)
116  bytes (variables)
1874 bytes (e-mail content)
4    bytes (attempts, sending, failed, encrypted)
57   bytes (created_at, updated_at, deleted_at)
... x 10.000 rows = ± 21.55 MB

With encryption the table size is ± 50.58 MB.
```

### Test mode (Optional)

When enabled, all newly created e-mails will be sent to the specified test e-mail address. This is turned off by default.

### E-mails to send per minute

To configure how many e-mails should be sent each command, please check the `limit` option. The default is `20` e-mails every command.

### Send e-mails immediately (Optional)

Useful during development when Laravel Scheduler is not running

To enable, set the following environment variable:

```
LARAVEL_DATABASE_EMAILS_SEND_IMMEDIATELY=true
```
