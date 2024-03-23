<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Console\Command;
use Throwable;

class SendEmailsCommand extends Command
{
    protected $signature = 'email:send';

    protected $description = 'Send all queued e-mails';

    public function handle(Store $store): void
    {
        $emails = $store->getQueue();

        if ($emails->isEmpty()) {
            $this->components->info('There is nothing to send.');

            return;
        }

        $this->components->info('Sending '.count($emails).' e-mail(s).');

        foreach ($emails as $email) {
            $recipients = implode(', ', array_keys($email->recipient));
            $line = str($email->subject)->limit(40).' - '.str($recipients)->limit(40);

            rescue(function () use ($email, $line) {
                $email->send();

                $this->components->twoColumnDetail($line, '<fg=green;options=bold>DONE</>');
            }, function (Throwable $e) use ($email, $line) {
                $email->markAsFailed($e);

                $this->components->twoColumnDetail($line, '<fg=red;options=bold>FAIL</>');
            });
        }

        $this->newLine();
    }
}
