<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Stackkit\LaravelDatabaseEmails\Email;

class EnvelopeTest extends TestCase
{
    public function test_it_can_set_the_envelope()
    {
        $email = Email::compose()
            ->envelope(
                (new Envelope())
                    ->subject('Hey')
                    ->from('asdf@gmail.com')
                    ->to('johndoe@example.com', 'janedoe@example.com')
            )
            ->content(
                (new Content())
                    ->view('tests::dummy')
                    ->with(['name' => 'John Doe'])
            )
            ->send();

        $this->assertEquals(['johndoe@example.com'], $email->recipient);
    }

}
