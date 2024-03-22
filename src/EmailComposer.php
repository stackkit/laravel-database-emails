<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Closure;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

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

    public ?Envelope $envelope = null;

    public ?Content $content = null;

    public ?array $attachments = null;

    public ?string $locale = null;

    /**
     * Create a new EmailComposer instance.
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function envelope(null|Envelope|Closure $envelope = null): self
    {
        if ($envelope instanceof Closure) {
            $this->envelope = $envelope($this->envelope ?: new Envelope());

            return $this;
        }

        $this->envelope = $envelope;

        return $this;
    }

    public function content(null|Content|Closure $content = null): self
    {
        if ($content instanceof Closure) {
            $this->content = $content($this->content ?: new Content());

            return $this;
        }

        $this->content = $content;

        return $this;
    }

    public function attachments(null|array|Closure $attachments = null): self
    {
        if ($attachments instanceof Closure) {
            $this->attachments = $attachments($this->attachments ?: []);

            return $this;
        }

        $this->attachments = $attachments;

        return $this;
    }

    public function user(User $user)
    {
        return $this->envelope(function (Envelope $envelope) use ($user) {
            $name = method_exists($user, 'preferredEmailName')
                ? $user->preferredEmailName()
                : ($user->name ?? null);

            $email = method_exists($user, 'preferredEmailAddress')
                ? $user->preferredEmailAddress()
                : $user->email;

            if ($user instanceof HasLocalePreference) {
                $this->locale = $user->preferredLocale();
            }

            return $envelope->to($email, $name);
        });
    }

    public function model(Model $model)
    {
        $this->setData('model', $model);

        return $this;
    }

    /**
     * Get the e-mail that is being composed.
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setData(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function getData(string $key, $default = null)
    {
        if (! is_null($default) && ! $this->hasData($key)) {
            return $default;
        }

        return $this->data[$key];
    }

    public function hasData(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function label(string $label): self
    {
        $this->email->label = $label;

        return $this;
    }

    public function later($scheduledAt): Email
    {
        $this->email->scheduled_at = Carbon::parse($scheduledAt);

        return $this->send();
    }

    public function queue(?string $connection = null, ?string $queue = null, $delay = null): Email
    {
        $connection = $connection ?: config('queue.default');
        $queue = $queue ?: 'default';

        $this->email->queued_at = now();

        $this->setData('queued', true);
        $this->setData('connection', $connection);
        $this->setData('queue', $queue);
        $this->setData('delay', $delay);

        return $this->send();
    }

    public function mailable(Mailable $mailable): self
    {
        $this->setData('mailable', $mailable);

        (new MailableReader())->read($this);

        return $this;
    }

    public function send(): Email
    {
        if ($this->envelope && $this->content) {
            (new MailableReader())->read($this);
        }

        if (! $this->email->from) {
            $this->email->from = [
                'address' => config('mail.from.address'),
                'name' => config('mail.from.name'),
            ];
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
