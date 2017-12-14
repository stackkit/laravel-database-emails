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
## Installation

First, require the package using composer.

```bash
$ composer require buildcode/laravel-database-emails
```

If you're running Laravel 5.5 or later you may skip this step. Add the service provider to your application.

```php
Buildcode\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider::class,
```

Publish the configuration files.

```bash
$ php artisan vendor:publish --provider=Buildcode\\LaravelDatabaseEmails\\LaravelDatabaseEmailsServiceProvider
```

Create the database table required for this package.

```bash
$ php artisan migrate
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
     $schedule->command('email:send')->everyMinute();
}
```

## Usage

### Create An Email

```php
Email::compose()
    ->label('welcome-mail-1.0')
    ->recipient('john@doe.com')
    ->subject('This is a test')
    ->view('emails.welcome')
    ->variables([
        'name' => 'John Doe',
    ])
    ->send();
```

### Specify Recipients

```php
$one = 'john@doe.com';
$multiple = ['john@doe.com', 'jane@doe.com'];

Email::compose()->recipient($one);
Email::compose()->recipient($multiple);

Email::compose()->cc($one);
Email::compose()->cc($multiple);

Email::compose()->bcc($one);
Email::compose()->bcc($multiple);
```

### Mailables

You may also pass a mailable to the e-mail composer.

```php
Email::compose()
	->mailable(new OrderShipped())
	->send();
```

### Attachments

```php
Email::compose()
	->attach('/path/to/file');
```

Or for in-memory attachments...

```php
Email::compose()
	->attachData('<p>Your order has shipped!</p>', 'order.html');
```

### Schedule An Email

You may schedule an e-mail by calling `later` instead of `send` at the end of the chain. You must provide a Carbon instance or a strtotime valid date.

```php
Email::compose()
  ->later('+2 hours');
```

### Manually Sending E-mails

If you're not running the cronjob and wish to send the queued e-mails, you can run the `email:send` command.

```bash
$ php artisan email:send
```

### Failed E-mails

By default, we will attempt to send an e-mail 3 times if it fails. If it still fails the 3rd time, it will permanently be marked as failed. You can change the number of times an e-mail should be attempted to be sent using the `retry.attempts` configuration.

### Retry sending failed e-mails

If you wish to retry sending failed e-mails, you may call the `email:retry` command. The command will grab any failed e-mail and push it onto the queue. You may also provide the id of a specific e-mail.

```bash
$ php artisan email:retry
# or...
$ php artisan email:retry 1
```

### Encryption

If you wish to encrypt your e-mails, please enable the `encrypt` option in the configuration file. This is disabled by default. Encryption and decryption will be handled by Laravel's built-in encryption mechanism. Please note that encrypting the e-mail body takes a lot of disk space.

### Testing Address

If you wish to send e-mails to a test address but don't necessarily want to use a service like mailtrap, please take a look at the `testing` configuration. This is turned off by default.

During the creation of an e-mail, the recipient will be replaced by the test e-mail. This is useful for local development or testing on a staging server.
