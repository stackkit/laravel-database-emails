# From 6.x to 7.x

## Database changes (Impact: high)

The way addresses are stored in the database has changed. Therefore, emails created in 6.x and below are incompatible.

When you upgrade, the existing database table will be renamed to "emails_old" and a new table will be created.

## Creating emails (Impact: high)

The way emails are composed has changed and now borrows a lot from Laravel's mailable.

```php
use Illuminate\Mail\Mailables\Content;
use Stackkit\LaravelDatabaseEmails\Attachment;use Stackkit\LaravelDatabaseEmails\Email;
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

## Encryption (Impact: moderate)

E-mail encryption has been removed from the package.
