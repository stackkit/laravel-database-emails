<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Throwable;

class SendEmailsCommand extends Command
{
    protected $signature = 'email:send';

    protected $description = 'Send all queued e-mails';

    public function handle(Store $store): void
    {
        $emails = $store->getQueue();

        if ($emails->isEmpty()) {
            $this->line('There is nothing to send.');

            return;
        }

        $progress = $this->output->createProgressBar($emails->count());

        foreach ($emails as $email) {
            $progress->advance();

            rescue(
                callback: fn () => $email->send(),
                rescue: fn (Throwable $e) => $email->markAsFailed($e)
            );
        }

        $progress->finish();

        $this->result($emails);
    }

    /**
     * Output a table with the cronjob result.
     */
    protected function result(LazyCollection $emails): void
    {
        $headers = ['ID', 'Recipient', 'Subject', 'Status'];

        $this->line("\n");

        $this->table($headers, $emails->map(function (Email $email) {
            return [
                $email->id,
                implode(',', array_column(Arr::wrap($email->recipient), 'recipient')),
                $email->subject,
                $email->failed ? 'Failed' : 'OK',
            ];
        }));
    }
}
