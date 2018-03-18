<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Console\Command;

class RetryFailedEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:retry {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry sending failed e-mails';

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
        if (get_class($this) === RetryFailedEmailsCommand::class) {
            $this->warn('This command is deprecated, please use email:resend instead');
        }

        $emails = $this->store->getFailed(
            $this->argument('id')
        );

        if ($emails->isEmpty()) {
            $this->line('There is nothing to reset.');
            return;
        }

        foreach ($emails as $email) {
            $email->retry();
        }

        $this->info('Reset ' . $emails->count() . ' ' . ngettext('e-mail', 'e-mails', $emails->count()) . '!');
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
}
