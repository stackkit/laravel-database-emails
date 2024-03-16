<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Mail\Mailable;

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

    /**
     * Create a new EmailComposer instance.
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
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
        return $this->setData('label', $label);
    }

    /**
     * Set the e-mail from address and aname.
     */
    public function from(?string $address = null, ?string $name = null): self
    {
        return $this->setData('from', compact('address', 'name'));
    }

    /**
     * Set the e-mail recipient(s).
     *
     * @param  string|array  $recipient
     */
    public function recipient($recipient): self
    {
        return $this->setData('recipient', $recipient);
    }

    /**
     * Define the carbon-copy address(es).
     *
     * @param  string|array  $cc
     */
    public function cc($cc): self
    {
        return $this->setData('cc', $cc);
    }

    /**
     * Define the blind carbon-copy address(es).
     *
     * @param  string|array  $bcc
     */
    public function bcc($bcc): self
    {
        return $this->setData('bcc', $bcc);
    }

    /**
     * Define the reply-to address(es).
     *
     * @param  string|array  $replyTo
     */
    public function replyTo($replyTo): self
    {
        return $this->setData('reply_to', $replyTo);
    }

    /**
     * Set the e-mail subject.
     */
    public function subject(string $subject): self
    {
        return $this->setData('subject', $subject);
    }

    /**
     * Set the e-mail view.
     */
    public function view(string $view): self
    {
        return $this->setData('view', $view);
    }

    /**
     * Set the e-mail variables.
     */
    public function variables(array $variables): self
    {
        return $this->setData('variables', $variables);
    }

    /**
     * Schedule the e-mail.
     *
     * @param  mixed  $scheduledAt
     */
    public function schedule($scheduledAt): Email
    {
        return $this->later($scheduledAt);
    }

    /**
     * Schedule the e-mail.
     *
     * @param  mixed  $scheduledAt
     */
    public function later($scheduledAt): Email
    {
        $this->setData('scheduled_at', $scheduledAt);

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
    public function attach(string $file, array $options = []): self
    {
        $attachments = $this->hasData('attachments') ? $this->getData('attachments') : [];

        $attachments[] = compact('file', 'options');

        return $this->setData('attachments', $attachments);
    }

    /**
     * Attach in-memory data as an attachment.
     */
    public function attachData(string $data, string $name, array $options = []): self
    {
        $attachments = $this->hasData('rawAttachments') ? $this->getData('rawAttachments') : [];

        $attachments[] = compact('data', 'name', 'options');

        return $this->setData('rawAttachments', $attachments);
    }

    /**
     * Send the e-mail.
     */
    public function send(): Email
    {
        (new Validator())->validate($this);

        (new Preparer())->prepare($this);

        if (Config::encryptEmails()) {
            (new Encrypter())->encrypt($this);
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
