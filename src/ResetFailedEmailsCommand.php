<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Console\Command;

class ResetFailedEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the failed e-mails and attempt to send them again';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $emails = Email::failed()->get();

        if ($emails->isEmpty()) {
            return $this->info('No failed emails found.');
        }

        $reset = 0;

        $emails->each(function ($email) use (&$reset) {
            $email->reset();

            $email->markAsDeleted();

            $reset++;
        });

        $this->info('Reset ' . $reset . ' ' . ngettext('e-mail', 'e-mails', $reset) . '!');
    }
}
