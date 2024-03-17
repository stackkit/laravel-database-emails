<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

class EmailComposer
{
    /**
     * The e-mail that is being composed.
     *
     * @var Email
     */
    private $email;

    /**
     * The e-email data.
     *
     * @var array
     */
    protected $data = [];

    public ?Envelope $envelope = null;

    public ?Content $content = null;

    public ?array $attachments = null;

    /**
     * Create a new EmailComposer instance.
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function envelope(null|Envelope|Closure $envelope = null): self
    {
        if ($envelope instanceof Closure) {
            $this->envelope = $envelope($this->envelope ?: new Envelope());

            return $this;
        }

        $this->envelope = $envelope;

        return $this;
    }

    public function content(null|Content|Closure $content = null): self
    {
        if ($content instanceof Closure) {
            $this->content = $content($this->content ?: new Content());

            return $this;
        }

        $this->content = $content;

        return $this;
    }

    public function attachments(null|array|Closure $attachments = null): self
    {
        if ($attachments instanceof Closure) {
            $this->attachments = $attachments($this->attachments ?: []);

            return $this;
        }

        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Get the e-mail that is being composed.
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * Set a data value.
     *
     * @param  mixed  $value
     */
    public function setData(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a data value.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getData(string $key, $default = null)
    {
        if (! is_null($default) && ! $this->hasData($key)) {
            return $default;
        }

        return $this->data[$key];
    }

    /**
     * Determine if the given data value was set.
     */
    public function hasData(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Set the e-mail label.
     */
    public function label(string $label): self
    {
        $this->email->label = $label;

        return $this;
    }

    /**
     * Schedule the e-mail.
     *
     * @param  mixed  $scheduledAt
     */
    public function later($scheduledAt): Email
    {
        $this->email->scheduled_at = Carbon::parse($scheduledAt);

        return $this->send();
    }

    /**
     * Queue the e-mail.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     */
    public function queue(?string $connection = null, ?string $queue = null, $delay = null): Email
    {
        $connection = $connection ?: config('queue.default');
        $queue = $queue ?: 'default';

        $this->email->queued_at = now();

        $this->setData('queued', true);
        $this->setData('connection', $connection);
        $this->setData('queue', $queue);
        $this->setData('delay', $delay);

        return $this->send();
    }

    /**
     * Set the Mailable.
     */
    public function mailable(Mailable $mailable): self
    {
        $this->setData('mailable', $mailable);

        (new MailableReader())->read($this);

        return $this;
    }

    /**
     * Attach a file to the e-mail.
     */
    //    public function attach(string $file, array $options = []): self
    //    {
    //        $attachments = $this->hasData('attachments') ? $this->getData('attachments') : [];
    //
    //        $attachments[] = compact('file', 'options');
    //
    //        return $this->setData('attachments', $attachments);
    //    }
    //
    //    /**
    //     * Attach in-memory data as an attachment.
    //     */
    //    public function attachData(string $data, string $name, array $options = []): self
    //    {
    //        $attachments = $this->hasData('rawAttachments') ? $this->getData('rawAttachments') : [];
    //
    //        $attachments[] = compact('data', 'name', 'options');
    //
    //        return $this->setData('rawAttachments', $attachments);
    //    }

    /**
     * Send the e-mail.
     */
    public function send(): Email
    {
        if ($this->envelope && $this->content) {
            (new MailableReader())->read($this);
        }

        if (! $this->email->from) {
            $this->email->from = [
                'address' => config('mail.from.address'),
                'name' => config('mail.from.name'),
            ];
        }

        $this->email->save();

        $this->email->refresh();

        if ($this->getData('queued', false) === true) {
            dispatch(new SendEmailJob($this->email))
                ->onConnection($this->getData('connection'))
                ->onQueue($this->getData('queue'))
                ->delay($this->getData('delay'));

            return $this->email;
        }

        if (Config::sendImmediately()) {
            $this->email->send();
        }

        return $this->email;
    }
}
