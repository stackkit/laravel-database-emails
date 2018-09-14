<?php

namespace Stackkit\LaravelDatabaseEmails;

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
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $configDir = $baseDir . 'config' . DIRECTORY_SEPARATOR;
        $migrationsDir = $baseDir . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR;

        $this->publishes([
            $configDir . 'laravel-database-emails.php' => config_path('laravel-database-emails.php'),
        ]);

        $this->loadMigrationsFrom([$migrationsDir]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            SendEmailsCommand::class,
        ]);
    }
}
