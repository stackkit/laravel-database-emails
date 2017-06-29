<?php

namespace Buildcode\LaravelDatabaseEmails;

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
     * Get all e-mails that are queued.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeQueued(Builder $query)
    {
        $maxAttempts = max(config('laravel-database-emails.retry.attempts', 1), 1);

        return $query
            ->whereNull('deleted_at')
            ->whereNull('sent_at')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', Carbon::now()->toDateTimeString());
            })
            ->where('failed', '=', 0)
            ->where('sending', '=', 0)
            ->where('attempts', '<', $maxAttempts);
    }

    /**
     * Get all e-mails that failed to be sent.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFailed(Builder $query)
    {
        return $query
            ->where('failed', '=', 1)
            ->whereNull('sent_at')
            ->whereNull('deleted_at');
    }

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
        } else {
            $var = $this->variables;
        }

        if (is_array($var)) {
            return $var;
        }

        if (is_string($var)) {
            return json_decode($var, 1);
        }

        return [];
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
     * Get the scheduled date.
     *
     * @return mixed
     */
    public function getScheduledDate()
    {
        return $this->scheduled_at;
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
     * Determine if a scheduled date has been set.
     *
     * @return bool
     */
    public function hasScheduledDate()
    {
        return !is_null($this->getScheduledDate());
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
     * Get the date when this e-mail was sent.
     *
     * @return string
     */
    public function getSentAt()
    {
        return $this->sent_at;
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
     * Get the decrypted property for the e-mail.
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
     * Increment the number of times this e-mail was attempted to be sent.
     *
     * @return void
     */
    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    /**
     * Mark the e-mail as sending.
     *
     * @return void
     */
    public function markAsSending()
    {
        $this->incrementAttempts();

        $this->update(['sending' => 1]);
    }

    /**
     * Mark the e-mail as sent.
     *
     * @return void
     */
    public function markAsSent()
    {
        $this->update([
            'sending' => 0,
            'sent_at' => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * Mark the e-mail as failed.
     *
     * @param string $error
     * @return void
     */
    public function markAsFailed($error = '')
    {
        $this->update([
            'sending' => 0,
            'failed'  => 1,
            'error'   => $error,
        ]);
    }

    /**
     * Mark the e-mail as deleted.
     *
     * @return void
     */
    public function markAsDeleted()
    {
        $this->update(['deleted_at' => Carbon::now()->toDateTimeString()]);
    }

    /**
     * Get the output command e-mail row.
     *
     * @return array
     */
    public function getTableRow()
    {
        return [
            $this->getId(),
            $this->getRecipient(),
            $this->getSubject(),
            is_null($this->getSentAt()) ? 'Failed' : 'OK',
        ];
    }

    /**
     * Reset the failed e-mail and attempt to re-send it.
     *
     * @return void
     */
    public function reset()
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