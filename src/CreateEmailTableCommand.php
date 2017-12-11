<?php

namespace Buildcode\LaravelDatabaseEmails;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class CreateEmailTableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'email:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the emails database table';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new queue job table command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem $files
     * @param  \Illuminate\Support\Composer $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $table = 'emails';

        $this->replaceMigration(
            $this->createBaseMigration($table), $table, Str::studly($table)
        );

        $this->info('Migration created successfully!');

        $this->composer->dumpAutoloads();
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
     * Create a base migration file for the table.
     *
     * @param  string $table
     * @return string
     */
    protected function createBaseMigration($table = 'emails')
    {
        return $this->laravel['migration.creator']->create(
            'create_' . $table . '_table', $this->laravel->databasePath() . '/migrations'
        );
    }

    /**
     * Replace the generated migration with the job table stub.
     *
     * @param  string $path
     * @param  string $table
     * @param  string $tableClassName
     * @return void
     */
    protected function replaceMigration($path, $table, $tableClassName)
    {
        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'],
            [$table, $tableClassName],
            $this->files->get(__DIR__ . '/../database/migrations/emails.stub')
        );

        $this->files->put($path, $stub);
    }
}
