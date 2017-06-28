<p align="center">
  <img src="/logo.png">
</p>

## Introduction

This is a package that stores and queues e-mails using a database table. Easily send e-mails using a cronjob or schedule e-mails that should be sent at a specific date and time.
## Installation

First, require the package using composer.

```bash
$ composer require buildcode/laravel-database-emails
```

If you're running Laravel 5.5 or later you may skip this step. Add the service provider to your application.

```php
Buildcode\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider::class,
```

Publish the configuration file.

```bash
$ php artisan vendor:publish --provider=Buildcode\\LaravelDatabaseEmails\\LaravelDatabaseEmailsServiceProvider
```

Create the e-mails database table migration.

```bash
$ php artisan email:table
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
Buildcode\LaravelDatabaseEmails\Email::compose()
    ->label('welcome-mail-1.0')
    ->recipient('john@doe.com')
    ->subject('This is a test')
    ->view('emails.welcome')
    ->variables([
        'name' => 'John Doe',
    ])
    ->send();
```

### Schedule An Email

You may schedule an e-mail by calling `schedule` instead of `send` at the end of the chain. You must provide a Carbon instance or a strtotime valid date.

```php
Buildcode\LaravelDatabaseEmails\Email::compose()
  ->label('welcome-mail-1.0')
  ->recipient('john@doe.com')
  ->subject('This is a test')
  ->view('emails.welcome')
  ->variables([
      'name' => 'John Doe',
  ])
  ->schedule('+2 hours');
```

### Manually Sending E-mails

If you're not running the cronjob and wish to send the queued e-mails, you can run the `email:send` command.

```bash
$ php artisan email:send
```

### Failed E-mails

By default, we will attempt to send an e-mail 3 times if it somehow fails.

### Retry sending failed e-mails

If you wish to reset failed e-mails and attempt to send them again, you may call the `email:failed` command. The command will grab any failed e-mail and push it onto the queue.

```bash
$ php artisan email:failed
```

### Encryption

If you wish to encrypt your e-mails, please enable the `encrypt` option in the configuration file. This is disabled by default. Encryption and decryption will be handled by Laravel's built-in encryption mechanism.

### Testing Address

If you wish to send e-mails to a test address but don't necessarily want to use a service like mailtrap, please take a look at the `testing` configuration. This is turned on by default.

During the creation of an e-mail, the recipient will be replaced by the test e-mail. This is useful for local development or testing on a staging server.

## Todo

- Add support for CC, BCC and attachments
- Add support for Mailables
- Add tests
