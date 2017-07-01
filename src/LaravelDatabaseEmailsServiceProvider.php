<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Support\ServiceProvider;

class LaravelDatabaseEmailsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

        $this->publishes([
            $dir . 'laravel-database-emails.php' => config_path('laravel-database-emails.php')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            CreateEmailTableCommand::class,
            SendEmailsCommand::class,
            RetryFailedEmailsCommand::class,
        ]);
    }
}
