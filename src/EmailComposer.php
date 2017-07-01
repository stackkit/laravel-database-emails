<?php

namespace Buildcode\LaravelDatabaseEmails;

use Buildcode\LaravelDatabaseEmails\Decorators\EncryptEmail;
use Buildcode\LaravelDatabaseEmails\Decorators\PrepareEmail;
use Carbon\Carbon;
use Psr\Log\InvalidArgumentException;

class EmailComposer
{
    private $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Set the e-mail label.
     *
     * @param string $label
     * @return static
     */
    public function label($label)
    {
        $this->email->label = $label;

        return $this;
    }

    /**
     * Set the e-mail recipient.
     *
     * @param string $recipient
     * @return static
     */
    public function recipient($recipient)
    {
        $this->email->recipient = $recipient;

        return $this;
    }

    /**
     * Send a copy of this e-mail to the given addresses.
     *
     * @param array $cc
     * @return static
     */
    public function cc($cc)
    {
        $this->email->cc = $cc;

        return $this;
    }

    /**
     * Send a blind copy to the given addresses.
     *
     * @param array $bcc
     * @return static
     */
    public function bcc($bcc)
    {
        $this->email->bcc = $bcc;

        return $this;
    }

    /**
     * Set the e-mail subject.
     *
     * @param string $subject
     * @return static
     */
    public function subject($subject)
    {
        $this->email->subject = $subject;

        return $this;
    }

    /**
     * Set the e-mail view.
     *
     * @param string $view
     * @return static
     */
    public function view($view)
    {
        $this->email->view = $view;

        return $this;
    }

    /**
     * Set the e-mail variables.
     *
     * @param array $variables
     * @return static
     */
    public function variables($variables)
    {
        $this->email->variables = $variables;

        return $this;
    }

    /**
     * Schedule the e-mail.
     *
     * @param mixed $scheduledFor
     * @return Email
     */
    public function schedule($scheduledFor)
    {
        $this->email->scheduled_at = $scheduledFor;

        return $this->send();
    }

    /**
     * Send the e-mail.
     *
     * @return Email
     */
    public function send()
    {
        Validator::validate($this->email);

        if (Config::encryptEmails()) {
            $email = (new EncryptEmail(new PrepareEmail($this->email)))->getEmail();
        } else {
            $email = (new PrepareEmail($this->email))->getEmail();
        }

        $email->save();

        return $email;
    }
}