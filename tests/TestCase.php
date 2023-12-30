<?php

namespace Tests;

use Stackkit\LaravelDatabaseEmails\Email;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $invalid;

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

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Stackkit\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider::class,
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
        $app['config']->set('database.connections.testbench', [
            'driver'   => getenv('CI_DB_DRIVER'),
            'host'     => getenv('CI_DB_HOST'),
            'port'     => getenv('CI_DB_PORT'),
            'database' => getenv('CI_DB_DATABASE'),
            'username' => getenv('CI_DB_USERNAME'),
            'password' => getenv('CI_DB_PASSWORD'),
            'prefix'   => '',
            'strict' => true,
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
