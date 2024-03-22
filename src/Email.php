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
 * @property int $id
 * @property string $label
 * @property array $recipient
 * @property array $from
 * @property array $cc
 * @property array $bcc
 * @property array $reply_to
 * @property string $subject
 * @property string $view
 * @property array $variables
 * @property string $body
 * @property array $attachments
 * @property int $attempts
 * @property int $sending
 * @property int $failed
 * @property int $error
 * @property ?Carbon $queued_at
 * @property ?Carbon $scheduled_at
 * @property ?Carbon $sent_at
 * @property ?Carbon $delivered_at
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

    protected $table = 'emails';

    protected $guarded = [];

    public static ?Closure $pruneQuery = null;

    public static function compose(): EmailComposer
    {
        return new EmailComposer(new static());
    }

    public function isSent(): bool
    {
        return ! is_null($this->sent_at);
    }

    public function hasFailed(): bool
    {
        return $this->failed == 1;
    }

    public function markAsSending(): void
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'sending' => 1,
        ]);
    }

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

    public function markAsFailed(Throwable $exception): void
    {
        $this->update([
            'sending' => 0,
            'failed' => 1,
            'error' => (string) $exception,
        ]);
    }

    public function send(): void
    {
        (new Sender())->send($this);
    }

    public static function pruneWhen(Closure $closure): void
    {
        static::$pruneQuery = $closure;
    }

    public function prunable(): Builder
    {
        if (static::$pruneQuery) {
            return (static::$pruneQuery)($this);
        }

        return $this->where('created_at', '<', now()->subMonths(6));
    }
}
