<?php

namespace Stackkit\LaravelDatabaseEmails;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

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
     *
     * @param Store $store
     */
    public function __construct(Store $store)
    {
        parent::__construct();

        $this->store = $store;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
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
            } catch (Exception $e) {
                $email->markAsFailed($e);
            }
        }

        $progress->finish();

        $this->result($emails);
    }

    /**
     * Execute the console command (backwards compatibility for Laravel 5.4 and below).
     *
     * @return void
     */
    public function fire()
    {
        $this->handle();
    }

    /**
     * Output a table with the cronjob result.
     *
     * @param Collection $emails
     * @return void
     */
    protected function result($emails)
    {
        $headers = ['ID', 'Recipient', 'Subject', 'Status'];

        $this->line("\n");

        $this->table($headers, $emails->map(function (Email $email) {
            return [
                $email->getId(),
                $email->getRecipientsAsString(),
                $email->getSubject(),
                $email->hasFailed() ? 'Failed' : 'OK',
            ];
        }));
    }
}
