<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Support\ServiceProvider;

class LaravelDatabaseEmailsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootConfig();
        $this->bootDatabase();
    }

    /**
     * Boot the config for the package.
     *
     * @return void
     */
    private function bootConfig(): void
    {
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $configDir = $baseDir . 'config' . DIRECTORY_SEPARATOR;

        $this->publishes([
            $configDir . 'laravel-database-emails.php' => config_path('laravel-database-emails.php'),
        ], 'laravel-database-emails-config');
    }

    /**
     * Boot the database for the package.
     *
     * @return void
     */
    private function bootDatabase(): void
    {
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $migrationsDir = $baseDir . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR;

        if ($this->app['config']->get('laravel-database-emails.manual_migrations')) {
            $this->publishes([
                $migrationsDir => "{$this->app->databasePath()}/migrations",
            ], 'laravel-database-emails-migrations');
        } else {
            $this->loadMigrationsFrom([$migrationsDir]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->commands([
            SendEmailsCommand::class,
        ]);
    }
}
