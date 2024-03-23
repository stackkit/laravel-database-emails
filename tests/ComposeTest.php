<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use PHPUnit\Framework\Attributes\Test;
use Stackkit\LaravelDatabaseEmails\Email;
use Workbench\App\Models\User;

class ComposeTest extends TestCase
{
    #[Test]
    public function models_can_be_attached(): void
    {
        $user = User::forceCreate([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'secret',
        ]);

        $email = Email::compose()
            ->user($user)
            ->model($user)
            ->envelope(fn (Envelope $envelope) => $envelope->subject('Hey'))
            ->content(fn (Content $content) => $content->view('welcome'))
            ->send();

        $this->assertEquals($email->model_type, $user->getMorphClass());
        $this->assertEquals($email->model_id, $user->getKey());
    }

    #[Test]
    public function models_can_be_empty(): void
    {
        $user = User::forceCreate([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'secret',
        ]);

        $email = Email::compose()
            ->user($user)
            ->envelope(fn (Envelope $envelope) => $envelope->subject('Hey'))
            ->content(fn (Content $content) => $content->view('welcome'))
            ->send();

        $this->assertNull($email->model_type);
        $this->assertNull($email->model_id);
    }
}
