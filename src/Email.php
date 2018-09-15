<?php

namespace Stackkit\LaravelDatabaseEmails;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $label
 * @property $recipient
 * @property $from
 * @property $cc
 * @property $bcc
 * @property $subject
 * @property $view
 * @property $variables
 * @property $body
 * @property $attachments
 * @property $attempts
 * @property $sending
 * @property $failed
 * @property $error
 * @property $encrypted
 * @property $scheduled_at
 * @property $sent_at
 * @property $delivered_at
 */
class Email extends Model
{
    use HasEncryptedAttributes;

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
        return $this->recipient;
    }

    /**
     * Get the e-mail recipient.
     *
     * @return string
     */
    public function getRecipientAttribute()
    {
        return $this->recipient;
    }

    /**
     * Get the e-mail from.
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get the e-mail from.
     *
     * @return string
     */
    public function getFromAttribute()
    {
        return $this->from;
    }

    /**
     * Get the e-mail from address.
     *
     * @return string|null
     */
    public function getFromAddress()
    {
        return $this->from['address'] ?? config('mail.from.address');
    }

    /**
     * Get the e-mail from address.
     *
     * @return string|null
     */
    public function getFromName()
    {
        return $this->from['name'] ?? config('mail.from.name');
    }

    /**
     * Get the e-mail recipient(s) as string.
     *
     * @return string
     */
    public function getRecipientsAsString()
    {
        $glue = ', ';

        return implode($glue, (array) $this->recipient);
    }

    /**
     * Get the e-mail CC addresses.
     *
     * @return array|string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Get the e-mail CC addresses.
     *
     * @return array
     */
    public function getCcAttribute()
    {
        return $this->cc;
    }

    /**
     * Get the e-mail BCC addresses.
     *
     * @return array|string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Get the e-mail BCC addresses.
     *
     * @return array
     */
    public function getBccAttribute()
    {
        return $this->bcc;
    }

    /**
     * Get the e-mail subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get the e-mail subject.
     *
     * @return string
     */
    public function getSubjectAttribute()
    {
        return $this->subject;
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
        return $this->variables;
    }

    /**
     * Get the e-mail variables.
     *
     * @return array
     */
    public function getVariablesAttribute()
    {
        return $this->variables;
    }

    /**
     * Get the e-mail body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get the e-mail body.
     *
     * @return string
     */
    public function getBodyAttribute()
    {
        return $this->body;
    }

    /**
     * Get the e-mail attachments.
     *
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
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
        return ! is_null($this->variables);
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
     * Determine if the e-mail should be sent with custom from values.
     *
     * @return bool
     */
    public function hasFrom()
    {
        return is_array($this->from) && count($this->from) > 0;
    }

    /**
     * Determine if the e-mail should be sent as a carbon copy.
     *
     * @return bool
     */
    public function hasCc()
    {
        return strlen($this->getOriginal('cc')) > 0;
    }

    /**
     * Determine if the e-mail should be sent as a blind carbon copy.
     *
     * @return bool
     */
    public function hasBcc()
    {
        return strlen($this->getOriginal('bcc')) > 0;
    }

    /**
     * Determine if the e-mail is scheduled to be sent later.
     *
     * @return bool
     */
    public function isScheduled()
    {
        return ! is_null($this->getScheduledDate());
    }

    /**
     * Determine if the e-mail is encrypted.
     *
     * @return bool
     */
    public function isEncrypted()
    {
        return (bool) $this->getOriginal('encrypted');
    }

    /**
     * Determine if the e-mail is sent.
     *
     * @return bool
     */
    public function isSent()
    {
        return ! is_null($this->sent_at);
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
            'failed'  => 0,
            'error'   => '',
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
            'error'   => (string) $exception,
        ]);
    }

    /**
     * Send the e-mail.
     *
     * @return void
     */
    public function send()
    {
        (new Sender)->send($this);
    }

    /**
     * Retry sending the e-mail.
     *
     * @return void
     */
    public function retry()
    {
        $retry = $this->replicate();

        $retry->fill(
            [
                'id'           => null,
                'attempts'     => 0,
                'sending'      => 0,
                'failed'       => 0,
                'error'        => null,
                'sent_at'      => null,
                'delivered_at' => null,
            ]
        );

        $retry->save();
    }
}
