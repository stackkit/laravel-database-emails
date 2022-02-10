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
     *
     * @param Email $email
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Get the e-mail that is being composed.
     *
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * Set a data value.
     *
     * @param string $key
     * @param mixed  $value
     * @return self
     */
    public function setData(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a data value.
     *
     * @param string $key
     * @param mixed  $default
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
     *
     * @param string $key
     * @return bool
     */
    public function hasData(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Set the e-mail label.
     *
     * @param string $label
     * @return self
     */
    public function label(string $label): self
    {
        return $this->setData('label', $label);
    }

    /**
     * Set the e-mail from address and aname.
     *
     * @param string|null $address
     * @param string|null $name
     * @return self
     */
    public function from(?string $address = null, ?string $name = null): self
    {
        return $this->setData('from', compact('address', 'name'));
    }

    /**
     * Set the e-mail recipient(s).
     *
     * @param string|array $recipient
     * @return self
     */
    public function recipient($recipient): self
    {
        return $this->setData('recipient', $recipient);
    }

    /**
     * Define the carbon-copy address(es).
     *
     * @param string|array $cc
     * @return self
     */
    public function cc($cc): self
    {
        return $this->setData('cc', $cc);
    }

    /**
     * Define the blind carbon-copy address(es).
     *
     * @param string|array $bcc
     * @return self
     */
    public function bcc($bcc): self
    {
        return $this->setData('bcc', $bcc);
    }

    /**
     * Set the e-mail subject.
     *
     * @param string $subject
     * @return self
     */
    public function subject(string $subject): self
    {
        return $this->setData('subject', $subject);
    }

    /**
     * Set the e-mail view.
     *
     * @param string $view
     * @return self
     */
    public function view(string $view): self
    {
        return $this->setData('view', $view);
    }

    /**
     * Set the e-mail variables.
     *
     * @param array $variables
     * @return self
     */
    public function variables(array $variables): self
    {
        return $this->setData('variables', $variables);
    }

    /**
     * Schedule the e-mail.
     *
     * @param mixed $scheduledAt
     * @return Email
     */
    public function schedule($scheduledAt): Email
    {
        return $this->later($scheduledAt);
    }

    /**
     * Schedule the e-mail.
     *
     * @param mixed $scheduledAt
     * @return Email
     */
    public function later($scheduledAt): Email
    {
        $this->setData('scheduled_at', $scheduledAt);

        return $this->send();
    }

    /**
     * Queue the e-mail.
     *
     * @param string|null $connection
     * @param string|null $queue
     * @param \DateTimeInterface|\DateInterval|int|null $delay
     * @return Email
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
     *
     * @param Mailable $mailable
     * @return self
     */
    public function mailable(Mailable $mailable): self
    {
        $this->setData('mailable', $mailable);

        (new MailableReader)->read($this);

        return $this;
    }

    /**
     * Attach a file to the e-mail.
     *
     * @param string $file
     * @param array  $options
     * @return self
     */
    public function attach(string $file, array $options = []): self
    {
        $attachments = $this->hasData('attachments') ? $this->getData('attachments') : [];

        $attachments[] = compact('file', 'options');

        return $this->setData('attachments', $attachments);
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string $data
     * @param  string $name
     * @param  array  $options
     * @return self
     */
    public function attachData(string $data, string $name, array $options = []): self
    {
        $attachments = $this->hasData('rawAttachments') ? $this->getData('rawAttachments') : [];

        $attachments[] = compact('data', 'name', 'options');

        return $this->setData('rawAttachments', $attachments);
    }

    /**
     * Send the e-mail.
     *
     * @return Email
     */
    public function send(): Email
    {
        (new Validator)->validate($this);

        (new Preparer)->prepare($this);

        if (Config::encryptEmails()) {
            (new Encrypter)->encrypt($this);
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
