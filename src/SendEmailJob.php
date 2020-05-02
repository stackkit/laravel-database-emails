<?php


namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function handle()
    {
        (new Sender())->send($this->email);
    }
}
