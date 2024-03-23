# From 6.x to 7.x

7.x is a bigger change which cleans up parts of the code base and modernizes the package. That means there are a few high-impact changes.

## Database changes (Impact: High)

The way addresses are stored in the database has changed. Therefore, emails created in 6.x and below are incompatible.

When you upgrade, the existing database table will be renamed to "emails_old" and a new table will be created.

The table migration now needs to be published first. Please run this command:

```shell
php artisan vendor:publish --tag=database-emails-migrations
```

Then, run the migration:

```shell
php artisan migrate
```

## Environment variables, configurations (Impact: High)

Environment variable names, as well as the config file name, have been shortened.

Please publish the new configuration file:

```shell
php artisan vendor:publish --tag=database-emails-config
```

You can remove the old configuration file.

Rename the following environments:

`LARAVEL_DATABASE_EMAILS_TESTING_ENABLED` `→` `DB_EMAILS_TESTING_ENABLED`
`LARAVEL_DATABASE_EMAILS_SEND_IMMEDIATELY` `->` `DB_EMAILS_SEND_IMMEDIATELY`

The following environments are new:

- `DB_EMAILS_ATTEMPTS`
- `DB_EMAILS_TESTING_EMAIL`
- `DB_EMAILS_LIMIT`
- `DB_EMAILS_IMMEDIATELY`

The following environments have been removed:

- `LARAVEL_DATABASE_EMAILS_MANUAL_MIGRATIONS` because migrations are always published.

## Creating emails (Impact: High)

The way emails are composed has changed and now borrows a lot from Laravel's mailable.

```php
use Illuminate\Mail\Mailables\Content;
use Stackkit\LaravelDatabaseEmails\Attachment;
use Stackkit\LaravelDatabaseEmails\Email;
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

## Encryption (Impact: moderate/low)

E-mail encryption has been removed from the package.
