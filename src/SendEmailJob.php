<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function handle(): void
    {
        $this->email->send();
    }
}
