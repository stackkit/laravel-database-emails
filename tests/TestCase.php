<?php

namespace Tests;

use Buildcode\LaravelDatabaseEmails\Email;
use Illuminate\Database\Schema\Blueprint;
use Eloquent;

class Testcase extends \Orchestra\Testbench\TestCase
{
    protected $invalid;

    function setUp()
    {
        parent::setUp();

        // set some invalid types for testing parameter values
        $this->invalid = [
            true,
            1,
            1.0,
            'test',
            new \stdClass(),
            (object) [],
            function () {},
        ];

        $this->createSchema();

        view()->addNamespace('tests', __DIR__ . '/views');
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('label')->nullable();
            $table->binary('recipient');
            $table->binary('cc')->nullable();
            $table->binary('bcc')->nullable();
            $table->binary('subject');
            $table->string('view', 255);
            $table->binary('variables')->nullable();
            $table->binary('body');
            $table->integer('attempts')->default(0);
            $table->boolean('sending')->default(0);
            $table->boolean('failed')->default(0);
            $table->text('error')->nullable();
            $table->boolean('encrypted')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Orchestra\Database\ConsoleServiceProvider::class,
            \Buildcode\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('laravel-database-emails.retry.attempts', 3);
    }

    public function createEmail($overwrite = [])
    {
        return Email::compose()
            ->label($overwrite['label'] ?? 'welcome')
            ->recipient($overwrite['recipient'] ?? 'john@doe.com')
            ->cc($overwrite['cc'] ?? null)
            ->bcc($overwrite['bcc'] ?? null)
            ->subject($overwrite['subject'] ?? 'test')
            ->view($overwrite['view'] ?? 'tests::dummy')
            ->variables($overwrite['variables'] ?? ['name' => 'John Doe']);
    }

    public function sendEmail($overwrite = [])
    {
        return $this->createEmail($overwrite)->send();
    }

    public function scheduleEmail($scheduledFor, $overwrite = [])
    {
        return $this->createEmail($overwrite)->schedule($scheduledFor);
    }
}