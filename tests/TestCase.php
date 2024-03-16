<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stackkit\LaravelDatabaseEmails\Email;
use Stackkit\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $invalid;

    use LazilyRefreshDatabase;

    public function setUp(): void
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
            function () {
            },
        ];

        view()->addNamespace('tests', __DIR__ . '/views');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Email::truncate();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelDatabaseEmailsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('laravel-database-emails.attempts', 3);
        $app['config']->set('laravel-database-emails.testing.enabled', false);
        $app['config']->set('laravel-database-emails.testing.email', 'test@email.com');

        $app['config']->set('database.default', 'testbench');
        $driver = env('DB_DRIVER', 'sqlite');
        $app['config']->set('database.connections.testbench', [
            'driver' => $driver,
            ...match($driver) {
                'sqlite' => [
                    'database' => ':memory:',
                ],
                'mysql' => [
                    'host' => '127.0.0.1',
                    'port' => 3307,
                ],
                'pgsql' => [
                    'host' => '127.0.0.1',
                    'port' => 5432,
                ],
            },
            'database' => 'test',
            'username' => 'test',
            'password' => 'test',
        ]);

        $app['config']->set('mail.driver', 'log');
    }

    public function createEmail($overwrite = [])
    {
        $params = array_merge([
            'label'     => 'welcome',
            'recipient' => 'john@doe.com',
            'cc'        => null,
            'bcc'       => null,
            'reply_to'  => null,
            'subject'   => 'test',
            'view'      => 'tests::dummy',
            'variables' => ['name' => 'John Doe'],
        ], $overwrite);

        return Email::compose()
            ->label($params['label'])
            ->recipient($params['recipient'])
            ->cc($params['cc'])
            ->bcc($params['bcc'])
            ->replyTo($params['reply_to'])
            ->subject($params['subject'])
            ->view($params['view'])
            ->variables($params['variables']);
    }

    public function composeEmail($overwrite = [])
    {
        return $this->createEmail($overwrite);
    }

    public function sendEmail($overwrite = [])
    {
        return $this->createEmail($overwrite)->send();
    }

    public function scheduleEmail($scheduledFor, $overwrite = [])
    {
        return $this->createEmail($overwrite)->schedule($scheduledFor);
    }

    public function queueEmail($connection = null, $queue = null, $delay = null, $overwrite = [])
    {
        return $this->createEmail($overwrite)->queue($connection, $queue, $delay);
    }
}
