<p align="center">
  <img src="/logo.png">
</p>
<p align="center">
<a href="https://travis-ci.org/stackkit/laravel-database-emails"><img src="https://travis-ci.org/stackkit/laravel-database-emails.svg?branch=master" alt="Build Status"></a>
<a href="https://packagist.org/packages/buildcode/laravel-database-emails"><img src="https://poser.pugx.org/buildcode/laravel-database-emails/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/buildcode/laravel-database-emails"><img src="https://poser.pugx.org/buildcode/laravel-database-emails/license.svg" alt="License"></a>
</p>

## Introduction

This is a package that stores and queues e-mails using a database table. Easily send e-mails using a cronjob and schedule e-mails that should be sent at a specific date and time.

## Table Of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Send an e-mail](#send-an-email)
  - [Specify multiple recipients](#specify-multiple-recipients)
  - [CC and BCC](#cc-and-bcc)
  - [Mailables](#using-mailables)
  - [Attachments](#attachments)
  - [Custom sender](#custom-sender)
  - [Scheduling](#scheduling)
  - [Resend failed e-mails](#resend-failed-e-mails)
  - [Encryption (optional)](#encryption-optional)
  - [Test mode (optional)](#test-mode-optional)

### Installation

First, require the package using composer.

```bash
composer require buildcode/laravel-database-emails
```

If you're running Laravel 5.5 or later you may skip this step. Add the service provider to your application.

```php
Buildcode\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider::class,
```

Publish the configuration files.

```bash
php artisan vendor:publish --provider=Buildcode\\LaravelDatabaseEmails\\LaravelDatabaseEmailsServiceProvider
```

Create the database table required for this package.

```bash
php artisan migrate
```

Now add the e-mail cronjob to your scheduler.

```php
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

### Usage

#### Send an email

```php
use Buildcode\LaravelDatabaseEmails\Email;

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

#### Specify multiple recipients

```php
use Buildcode\LaravelDatabaseEmails\Email;

Buildcode\LaravelDatabaseEmails\Email::compose()
    ->recipient([
        'john@doe.com',
        'jane@doe.com'
    ]);
```

#### CC and BCC

```php
use Buildcode\LaravelDatabaseEmails\Email;

Email::compose()
    ->cc('john@doe.com')
    ->cc(['john@doe.com', 'jane@doe.com'])
    ->bcc('john@doe.com')
    ->bcc(['john@doe.com', 'jane@doe.com']);
```

#### Using mailables

You may also pass a mailable to the e-mail composer.

```php
use Buildcode\LaravelDatabaseEmails\Email;

Email::compose()
    ->mailable(new OrderShipped())
    ->send();
```

#### Attachments

```php
use Buildcode\LaravelDatabaseEmails\Email;

Email::compose()
    ->attach('/path/to/file');
```

Or for in-memory attachments:

```php
use Buildcode\LaravelDatabaseEmails\Email;

Email::compose()
    ->attachData('<p>Your order has shipped!</p>', 'order.html');
```

#### Custom Sender

```php
use Buildcode\LaravelDatabaseEmails\Email;

Email::compose()
    ->from('john@doe.com', 'John Doe');
```

#### Scheduling

You may schedule an e-mail by calling `later` instead of `send`. You must provide a Carbon instance or a strtotime valid date.

```php
use Buildcode\LaravelDatabaseEmails\Email;

Email::compose()
    ->later('+2 hours');
```

#### Resend failed e-mails

##### Resend all failed e-mails

```bash
php artisan email:resend
```

##### Resend a specific failed e-mail

```bash
php artisan email:resend 1
```

#### Encryption (Optional)

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

#### Test mode (Optional)

When enabled, all newly created e-mails will be sent to the specified test e-mail address. This is turned off by default.