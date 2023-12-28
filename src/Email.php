<?php

declare(strict_types=1);

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
 * @property $reply_to
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
 * @property $queued_at
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
    public static function compose(): EmailComposer
    {
        return new EmailComposer(new static());
    }

    /**
     * Get the e-mail id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the e-mail label.
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Get the e-mail recipient.
     *
     * @return string|array
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Get the e-mail recipient.
     *
     * @return string|array
     */
    public function getRecipientAttribute()
    {
        return $this->recipient;
    }

    /**
     * Get the e-mail from.
     *
     * @return array|null
     */
    public function getFrom(): ?array
    {
        return $this->from;
    }

    /**
     * Get the e-mail from.
     *
     * @return array|null
     */
    public function getFromAttribute(): ?array
    {
        return $this->from;
    }

    /**
     * Get the e-mail from address.
     *
     * @return string|null
     */
    public function getFromAddress(): ?string
    {
        return $this->from['address'] ?? config('mail.from.address');
    }

    /**
     * Get the e-mail from address.
     *
     * @return string|null
     */
    public function getFromName(): ?string
    {
        return $this->from['name'] ?? config('mail.from.name');
    }

    /**
     * Get the e-mail recipient(s) as string.
     *
     * @return string
     */
    public function getRecipientsAsString(): string
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
     * @return array|string
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
     * @return array|string
     */
    public function getBccAttribute()
    {
        return $this->bcc;
    }

    /**
     * Get the e-mail reply-to addresses.
     *
     * @return array|string
     */
    public function getReplyTo()
    {
        return $this->reply_to;
    }

    /**
     * Get the e-mail reply-to addresses.
     *
     * @return array|string
     */
    public function getReplyToAttribute()
    {
        return $this->reply_to;
    }

    /**
     * Get the e-mail subject.
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get the e-mail subject.
     *
     * @return string
     */
    public function getSubjectAttribute(): string
    {
        return $this->subject;
    }

    /**
     * Get the e-mail view.
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Get the e-mail variables.
     *
     * @return array|null
     */
    public function getVariables(): ?array
    {
        return $this->variables;
    }

    /**
     * Get the e-mail variables.
     *
     * @return array|null
     */
    public function getVariablesAttribute(): ?array
    {
        return $this->variables;
    }

    /**
     * Get the e-mail body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get the e-mail body.
     *
     * @return string
     */
    public function getBodyAttribute(): string
    {
        return $this->body;
    }

    /**
     * Get the e-mail attachments.
     *
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Get the number of times this e-mail was attempted to send.
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Get the queued date.
     *
     * @return string|null
     */
    public function getQueuedDate(): ?string
    {
        return $this->queued_at;
    }

    /**
     * Get the queued date as a Carbon instance.
     *
     * @return Carbon
     */
    public function getQueuedDateAsCarbon(): Carbon
    {
        if ($this->queued_at instanceof Carbon) {
            return $this->queued_at;
        }

        return Carbon::parse($this->queued_at);
    }

    /**
     * Get the scheduled date.
     *
     * @return string|null
     */
    public function getScheduledDate(): ?string
    {
        return $this->scheduled_at;
    }

    /**
     * Determine if the e-mail has variables defined.
     *
     * @return bool
     */
    public function hasVariables(): bool
    {
        return ! is_null($this->variables);
    }

    /**
     * Get the scheduled date as a Carbon instance.
     *
     * @return Carbon
     */
    public function getScheduledDateAsCarbon(): Carbon
    {
        if ($this->scheduled_at instanceof Carbon) {
            return $this->scheduled_at;
        }

        return Carbon::parse($this->scheduled_at);
    }

    /**
     * Get the send date for this e-mail.
     *
     * @return string|null
     */
    public function getSendDate(): ?string
    {
        return $this->sent_at;
    }

    /**
     * Get the send error.
     *
     * @return string|string
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Determine if the e-mail should be sent with custom from values.
     *
     * @return bool
     */
    public function hasFrom(): bool
    {
        return is_array($this->from) && count($this->from) > 0;
    }

    /**
     * Determine if the e-mail should be sent as a carbon copy.
     *
     * @return bool
     */
    public function hasCc(): bool
    {
        return strlen($this->getRawDatabaseValue('cc')) > 0;
    }

    /**
     * Determine if the e-mail should be sent as a blind carbon copy.
     *
     * @return bool
     */
    public function hasBcc(): bool
    {
        return strlen($this->getRawDatabaseValue('bcc')) > 0;
    }

    /**
     * Determine if the e-mail should sent with reply-to.
     *
     * @return bool
     */
    public function hasReplyTo(): bool
    {
        return strlen($this->getRawDatabaseValue('reply_to') ?: '') > 0;
    }

    /**
     * Determine if the e-mail is scheduled to be sent later.
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return ! is_null($this->getScheduledDate());
    }

    /**
     * Determine if the e-mail is encrypted.
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return (bool) $this->getRawDatabaseValue('encrypted');
    }

    /**
     * Determine if the e-mail is sent.
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return ! is_null($this->sent_at);
    }

    /**
     * Determine if the e-mail failed to be sent.
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->failed == 1;
    }

    /**
     * Mark the e-mail as sending.
     *
     * @return void
     */
    public function markAsSending(): void
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
    public function markAsSent(): void
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
    public function markAsFailed(Exception $exception): void
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
    public function send(): void
    {
        (new Sender())->send($this);
    }

    /**
     * Retry sending the e-mail.
     *
     * @return void
     */
    public function retry(): void
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

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getRawDatabaseValue(string $key = null, $default = null)
    {
        if (method_exists($this, 'getRawOriginal')) {
            return $this->getRawOriginal($key, $default);
        }

        return $this->getOriginal($key, $default);
    }
}
