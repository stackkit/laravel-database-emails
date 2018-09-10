<?php

namespace Buildcode\LaravelDatabaseEmails;

class ResendEmailsCommand extends RetryFailedEmailsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:resend {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resend failed e-mails';
}
