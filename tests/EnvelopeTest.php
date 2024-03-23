<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use PHPUnit\Framework\Attributes\Test;
use Stackkit\LaravelDatabaseEmails\Email;
use Workbench\App\Models\User;
use Workbench\App\Models\UserWithPreferredEmail;
use Workbench\App\Models\UserWithPreferredLocale;
use Workbench\App\Models\UserWithPreferredName;

class EnvelopeTest extends TestCase
{
    public function test_it_can_set_the_envelope()
    {
        $email = Email::compose()
            ->envelope(
                (new Envelope())
                    ->subject('Hey')
                    ->from('asdf@gmail.com')
                    ->to(['johndoe@example.com', 'janedoe@example.com'])
            )
            ->content(
                (new Content())
                    ->view('tests::dummy')
                    ->with(['name' => 'John Doe'])
            )
            ->send();

        $this->assertEquals([
            'johndoe@example.com' => null,
            'janedoe@example.com' => null,
        ], $email->recipient);
    }

    #[Test]
    public function test_it_can_pass_user_models()
    {
        $user = (new User())->forceFill([
            'email' => 'johndoe@example.com',
            'name' => 'J. Doe',
        ]);

        $email = Email::compose()
            ->user($user)
            ->envelope(fn (Envelope $envelope) => $envelope->subject('Hey'))
            ->content(fn (Content $content) => $content->view('welcome'))
            ->send();

        $this->assertEquals(
            [
                'johndoe@example.com' => 'J. Doe',
            ],
            $email->recipient
        );
    }

    #[Test]
    public function users_can_have_a_preferred_email()
    {
        $user = (new UserWithPreferredEmail())->forceFill([
            'email' => 'johndoe@example.com',
            'name' => 'J. Doe',
        ]);

        $email = Email::compose()
            ->user($user)
            ->envelope(fn (Envelope $envelope) => $envelope->subject('Hey'))
            ->content(fn (Content $content) => $content->view('welcome'))
            ->send();

        $this->assertEquals(
            [
                'noreply@abc.com' => 'J. Doe',
            ],
            $email->recipient
        );
    }

    #[Test]
    public function users_can_have_a_preferred_name()
    {
        $user = (new UserWithPreferredName())->forceFill([
            'email' => 'johndoe@example.com',
            'name' => 'J. Doe',
        ]);

        $email = Email::compose()
            ->user($user)
            ->envelope(fn (Envelope $envelope) => $envelope->subject('Hey'))
            ->content(fn (Content $content) => $content->view('welcome'))
            ->send();

        $this->assertEquals(
            [
                'johndoe@example.com' => 'J.D.',
            ],
            $email->recipient
        );
    }

    #[Test]
    public function users_can_have_a_preferred_locale()
    {
        $nonLocaleUser = (new User())->forceFill([
            'email' => 'johndoe@example.com',
            'name' => 'J. Doe',
        ]);

        $emailForNonLocaleUser = Email::compose()
            ->user($nonLocaleUser)
            ->envelope(fn (Envelope $envelope) => $envelope->subject('Hey'))
            ->content(fn (Content $content) => $content->view('locale-email'))
            ->send();

        $localeUser = (new UserWithPreferredLocale())->forceFill([
            'email' => 'johndoe@example.com',
            'name' => 'J. Doe',
        ]);

        $emailForLocaleUser = Email::compose()
            ->user($localeUser)
            ->envelope(fn (Envelope $envelope) => $envelope->subject('Hey'))
            ->content(fn (Content $content) => $content->view('locale-email'))
            ->send();

        $this->assertStringContainsString('Hello!', $emailForNonLocaleUser->body);
        $this->assertStringContainsString('Kumusta!', $emailForLocaleUser->body);
    }
}
