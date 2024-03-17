<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Throwable;

class SendEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all queued e-mails';

    /**
     * The e-mail repository.
     *
     * @var Store
     */
    protected $store;

    /**
     * Create a new SendEmailsCommand instance.
     */
    public function __construct(Store $store)
    {
        parent::__construct();

        $this->store = $store;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $emails = $this->store->getQueue();

        if ($emails->isEmpty()) {
            $this->line('There is nothing to send.');

            return;
        }

        $progress = $this->output->createProgressBar($emails->count());

        foreach ($emails as $email) {
            $progress->advance();

            try {
                $email->send();
            } catch (Throwable $e) {
                $email->markAsFailed($e);
            }
        }

        $progress->finish();

        $this->result($emails);
    }

    /**
     * Output a table with the cronjob result.
     */
    protected function result(Collection $emails): void
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
