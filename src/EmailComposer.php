<?php

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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set a data value.
     *
     * @param string $key
     * @param mixed  $value
     * @return static
     */
    public function setData($key, $value)
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
    public function getData($key, $default = null)
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
    public function hasData($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Set the e-mail label.
     *
     * @param string $label
     * @return static
     */
    public function label($label)
    {
        return $this->setData('label', $label);
    }

    /**
     * Set the e-mail from address and aname.
     *
     * @param array $address
     * @param array $name
     * @return static
     */
    public function from($address = null, $name = null)
    {
        return $this->setData('from', compact('address', 'name'));
    }

    /**
     * Set the e-mail recipient(s).
     *
     * @param string|array $recipient
     * @return static
     */
    public function recipient($recipient)
    {
        return $this->setData('recipient', $recipient);
    }

    /**
     * Define the carbon-copy address(es).
     *
     * @param string|array $cc
     * @return static
     */
    public function cc($cc)
    {
        return $this->setData('cc', $cc);
    }

    /**
     * Define the blind carbon-copy address(es).
     *
     * @param string|array $bcc
     * @return static
     */
    public function bcc($bcc)
    {
        return $this->setData('bcc', $bcc);
    }

    /**
     * Set the e-mail subject.
     *
     * @param string $subject
     * @return static
     */
    public function subject($subject)
    {
        return $this->setData('subject', $subject);
    }

    /**
     * Set the e-mail view.
     *
     * @param string $view
     * @return static
     */
    public function view($view)
    {
        return $this->setData('view', $view);
    }

    /**
     * Set the e-mail variables.
     *
     * @param array $variables
     * @return EmailComposer
     */
    public function variables($variables)
    {
        return $this->setData('variables', $variables);
    }

    /**
     * Schedule the e-mail.
     *
     * @param mixed $scheduledAt
     * @return Email
     */
    public function schedule($scheduledAt)
    {
        return $this->later($scheduledAt);
    }

    /**
     * Schedule the e-mail.
     *
     * @param mixed $scheduledAt
     * @return Email
     */
    public function later($scheduledAt)
    {
        $this->setData('scheduled_at', $scheduledAt);

        return $this->send();
    }

    /**
     * Set the Mailable.
     *
     * @param Mailable $mailable
     * @return static
     */
    public function mailable(Mailable $mailable)
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
     * @return static
     */
    public function attach($file, $options = [])
    {
        $validFileName = (is_string($file) && strlen($file) > 0);

        if (! $validFileName) {
            return $this;
        }

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
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $validData = (is_string($data) && strlen($data) > 0);

        if (! $validData) {
            return $this;
        }

        $attachments = $this->hasData('rawAttachments') ? $this->getData('rawAttachments') : [];

        $attachments[] = compact('data', 'name', 'options');

        return $this->setData('rawAttachments', $attachments);
    }

    /**
     * Send the e-mail.
     *
     * @return Email
     */
    public function send()
    {
        (new Validator)->validate($this);

        (new Preparer)->prepare($this);

        if (Config::encryptEmails()) {
            (new Encrypter)->encrypt($this);
        }

        $this->email->save();

        $this->email->refresh();

        if (Config::sendImmediately()) {
            $this->email->send();
        }

        return $this->email;
    }
}
