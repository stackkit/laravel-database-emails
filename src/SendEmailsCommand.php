<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;

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
    protected $description = 'Send e-mails in the system';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $emails = Email::queued()->get();

        if ($emails->isEmpty()) {
            return $this->info('No e-mails to be sent.');
        }

        $bar = $this->output->createProgressBar($emails->count());

        $table = [
            'headers' => ['ID', 'Recipient', 'Subject', 'Status'],
            'rows'    => [],
        ];

        $emails->each(function (Email $email) use (&$bar, &$table) {
            $bar->advance();

            $email->markAsSending();

            try {
                Mail::send([], [], function ($message) use ($email) {
                    $message->to($email->getRecipient())
                        ->subject($email->getSubject())
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->setBody($email->getBody(), 'text/html');
                });

                $email->markAsSent();
            } catch (Exception $e) {
                if ($email->getAttempts() >= config('laravel-database-emails.retry.attempts', 3)) {
                    $email->markAsFailed($e->getMessage());
                }
            }

            $table['rows'][] = $email->getTableRow();
        });

        $bar->finish();

        $this->line('');
        $this->line('');

        $this->table($table['headers'], $table['rows']);
    }
}
