<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

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
 * @property $queued_at
 * @property $scheduled_at
 * @property $sent_at
 * @property $delivered_at
 */
class Email extends Model
{
    use MassPrunable;

    protected $casts = [
        'failed' => 'boolean',
        'recipient' => 'json',
        'from' => 'json',
        'cc' => 'json',
        'bcc' => 'json',
        'reply_to' => 'json',
        'variables' => 'json',
        'attachments' => 'json',
    ];

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

    public static ?Closure $pruneQuery = null;

    /**
     * Compose a new e-mail.
     */
    public static function compose(): EmailComposer
    {
        return new EmailComposer(new static());
    }

    /**
     * Get the e-mail from address.
     */
    public function getFromAddress(): ?string
    {
        return $this->from['address'];
    }

    /**
     * Get the e-mail from address.
     */
    public function getFromName(): ?string
    {
        return $this->from['name'];
    }

    /**
     * Determine if the e-mail is sent.
     */
    public function isSent(): bool
    {
        return ! is_null($this->sent_at);
    }

    /**
     * Determine if the e-mail failed to be sent.
     */
    public function hasFailed(): bool
    {
        return $this->failed == 1;
    }

    /**
     * Mark the e-mail as sending.
     */
    public function markAsSending(): void
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'sending' => 1,
        ]);
    }

    /**
     * Mark the e-mail as sent.
     */
    public function markAsSent(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $this->update([
            'sending' => 0,
            'sent_at' => $now,
            'failed' => 0,
            'error' => '',
        ]);
    }

    /**
     * Mark the e-mail as failed.
     */
    public function markAsFailed(Throwable $exception): void
    {
        $this->update([
            'sending' => 0,
            'failed' => 1,
            'error' => (string) $exception,
        ]);
    }

    /**
     * Send the e-mail.
     */
    public function send(): void
    {
        (new Sender())->send($this);
    }

    /**
     * Retry sending the e-mail.
     */
    public function retry(): void
    {
        $retry = $this->replicate();

        $retry->fill(
            [
                'id' => null,
                'attempts' => 0,
                'sending' => 0,
                'failed' => 0,
                'error' => null,
                'sent_at' => null,
                'delivered_at' => null,
            ]
        );

        $retry->save();
    }

    /**
     * @return void
     */
    public static function pruneWhen(Closure $closure)
    {
        static::$pruneQuery = $closure;
    }

    /**
     * @return Builder
     */
    public function prunable()
    {
        if (static::$pruneQuery) {
            return (static::$pruneQuery)($this);
        }

        return $this->where('created_at', '<', now()->subMonths(6));
    }
}
