<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Support\ServiceProvider;

class LaravelDatabaseEmailsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->bootConfig();
        $this->bootDatabase();
    }

    /**
     * Boot the config for the package.
     */
    private function bootConfig(): void
    {
        $baseDir = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        $configDir = $baseDir.'config'.DIRECTORY_SEPARATOR;

        $this->publishes([
            $configDir.'laravel-database-emails.php' => config_path('laravel-database-emails.php'),
        ], 'database-emails-config');
    }

    /**
     * Boot the database for the package.
     */
    private function bootDatabase(): void
    {
        $baseDir = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        $migrationsDir = $baseDir.'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR;

        $this->publishes([
            $migrationsDir => "{$this->app->databasePath()}/migrations",
        ], 'database-emails-migrations');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->commands([
            SendEmailsCommand::class,
        ]);
    }
}
