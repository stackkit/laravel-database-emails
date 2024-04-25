<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Stackkit\LaravelDatabaseEmails\Email;
use Stackkit\LaravelDatabaseEmails\LaravelDatabaseEmailsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $invalid;

    use RefreshDatabase;
    use WithWorkbench;

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

        view()->addNamespace('tests', __DIR__.'/views');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

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
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database-emails.attempts', 3);
        $app['config']->set('database-emails.testing.enabled', false);
        $app['config']->set('database-emails.testing.email', 'test@email.com');

        $app['config']->set('filesystems.disks.my-custom-disk', [
            'driver' => 'local',
            'root' => __DIR__.'/../workbench/storage/app/public',
        ]);

        $app['config']->set('database.default', 'testbench');
        $driver = env('DB_DRIVER', 'sqlite');
        $app['config']->set('database.connections.testbench', [
            'driver' => $driver,
            ...match ($driver) {
                'sqlite' => [
                    'database' => database_path('database.sqlite'),
                ],
                'mysql' => [
                    'host' => '127.0.0.1',
                    'port' => 3307,
                    'database' => 'test',
                    'username' => 'test',
                    'password' => 'test',
                ],
                'pgsql' => [
                    'host' => '127.0.0.1',
                    'port' => 5432,
                    'database' => 'test',
                    'username' => 'test',
                    'password' => 'test',
                ],
            },
        ]);

        $app['config']->set('mail.driver', 'log');

        $app['config']->set('mail.from.name', 'Laravel');
    }

    public function createEmail($overwrite = [])
    {
        $params = array_merge([
            'label' => 'welcome',
            'recipient' => 'john@doe.com',
            'cc' => null,
            'bcc' => null,
            'reply_to' => null,
            'subject' => 'test',
            'view' => 'tests::dummy',
            'variables' => ['name' => 'John Doe'],
            'from' => null,
        ], $overwrite);

        return Email::compose()
            ->label($params['label'])
            ->envelope(fn (Envelope $envelope) => $envelope
                ->to($params['recipient'])
                ->when($params['cc'], fn ($envelope) => $envelope->cc($params['cc']))
                ->when($params['bcc'], fn ($envelope) => $envelope->bcc($params['bcc']))
                ->when($params['reply_to'], fn ($envelope) => $envelope->replyTo($params['reply_to']))
                ->when($params['from'], fn (Envelope $envelope) => $envelope->from($params['from']))
                ->subject($params['subject']))
            ->content(fn (Content $content) => $content
                ->view($params['view'])
                ->with($params['variables'])
            );
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
        return $this->createEmail($overwrite)->later($scheduledFor);
    }

    public function queueEmail($connection = null, $queue = null, $delay = null, $overwrite = [], ?string $jobClass = null)
    {
        return $this->createEmail($overwrite)->queue($connection, $queue, $delay, $jobClass);
    }
}
