<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;

class Email extends Model
{
    /**
     * The table in which the e-mails are stored.
     *
     * @var string
     */
    protected $table = 'emails';

    /**
     * The guarded fields.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Compose a new e-mail.
     *
     * @return EmailComposer
     */
    public static function compose()
    {
        return new EmailComposer(new self);
    }

    /**
     * Get the e-mail id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the e-mail label.
     *
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get the e-mail recipient.
     *
     * @return string
     */
    public function getRecipient()
    {
        return $this->getEmailProperty('recipient');
    }

    /**
     * Get the e-mail CC addresses.
     *
     * @return array|string
     */
    public function getCc()
    {
        if ($this->exists) {
            $cc = $this->getEmailProperty('cc');

            return json_decode($cc, 1);
        }

        return $this->cc;
    }

    /**
     * Get the e-mail BCC addresses.
     *
     * @return array|string
     */
    public function getBcc()
    {
        if ($this->exists) {
            $bcc = $this->getEmailProperty('bcc');

            return json_decode($bcc, 1);
        }

        return $this->bcc;
    }

    /**
     * Get the e-mail subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getEmailProperty('subject');
    }

    /**
     * Get the e-mail view.
     *
     * @return string
     */
    public function getView()
    {

        return $this->view;
    }

    /**
     * Get the e-mail variables.
     *
     * @return array
     */
    public function getVariables()
    {
        if ($this->exists) {
            $var = $this->getEmailProperty('variables');

            return json_decode($var, 1);
        }

        if (is_string($this->variables)) {
            return json_decode($this->variables, 1);
        }

        return $this->variables;
    }

    /**
     * Get the e-mail body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getEmailProperty('body');
    }

    /**
     * Get the number of times this e-mail was attempted to send.
     *
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }


    /**
     * Get the scheduled date.
     *
     * @return mixed
     */
    public function getScheduledDate()
    {
        return $this->scheduled_at;
    }

    /**
     * Determine if the e-mail has variables defined.
     *
     * @return bool
     */
    public function hasVariables()
    {
        return !is_null($this->variables);
    }

    /**
     * Get the scheduled date as a Carbon instance.
     *
     * @return Carbon
     */
    public function getScheduledDateAsCarbon()
    {
        if ($this->scheduled_at instanceof Carbon) {
            return $this->scheduled_at;
        }

        return Carbon::parse($this->scheduled_at);
    }

    /**
     * Get the send date for this e-mail.
     *
     * @return string
     */
    public function getSendDate()
    {
        return $this->sent_at;
    }

    /**
     * Get the send error.
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Determine if the e-mail should be sent as a carbon copy.
     *
     * @return bool
     */
    public function hasCc()
    {
        return !is_null($this->cc);
    }

    /**
     * Determine if the e-mail should be sent as a blind carbon copy.
     *
     * @return bool
     */
    public function hasBcc()
    {
        return !is_null($this->bcc);
    }

    /**
     * Determine if the e-mail is scheduled to be sent later.
     *
     * @return bool
     */
    public function isScheduled()
    {
        return !is_null($this->getScheduledDate());
    }


    /**
     * Determine if the e-mail is encrypted.
     *
     * @return bool
     */
    public function isEncrypted()
    {
        return !!$this->encrypted;
    }

    /**
     * Determine if the e-mail is sent.
     *
     * @return bool
     */
    public function isSent()
    {
        return !is_null($this->sent_at);
    }

    /**
     * Determine if the e-mail failed to be sent.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->failed == 1;
    }

    /**
     * Get a decrypted property.
     *
     * @param string $property
     * @return mixed
     */
    private function getEmailProperty($property)
    {
        if ($this->exists && $this->isEncrypted()) {
            try {
                return decrypt($this->{$property});
            } catch (DecryptException $e) {
                return '';
            }
        }

        return $this->{$property};
    }

    /**
     * Mark the e-mail as sending.
     *
     * @return void
     */
    public function markAsSending()
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'sending'  => 1,
        ]);
    }

    /**
     * Mark the e-mail as sent.
     *
     * @return void
     */
    public function markAsSent()
    {
        $now = Carbon::now()->toDateTimeString();

        $this->update([
            'sending' => 0,
            'sent_at' => $now,
        ]);
    }

    /**
     * Mark the e-mail as failed.
     *
     * @param Exception $exception
     * @return void
     */
    public function markAsFailed(Exception $exception)
    {
        $this->update([
            'sending' => 0,
            'failed'  => 1,
            'error'   => $exception->getMessage(),
        ]);
    }

    /**
     * Send the e-mail.
     *
     * @return void
     */
    public function send()
    {
        if ($this->isSent()) {
            return;
        }

        $this->markAsSending();

        Event::dispatch('before.send');

        Mail::send([], [], function ($message) {
            $message->to($this->getRecipient())
                ->cc($this->hasCc() ? $this->getCc() : [])
                ->bcc($this->hasBcc() ? $this->getBcc() : [])
                ->subject($this->getSubject())
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->setBody($this->getBody(), 'text/html');
        });

        $this->markAsSent();
    }

    /**
     * Retry sending the e-mail.
     *
     * @return void
     */
    public function retry()
    {
        $retry = new static;

        $retry->fill(array_merge(
            $this->toArray(),
            [
                'id'           => null,
                'attempts'     => 0,
                'sending'      => 0,
                'failed'       => 0,
                'error'        => null,
                'sent_at'      => null,
                'delivered_at' => null,
            ]
        ));

        $retry->save();
    }
}