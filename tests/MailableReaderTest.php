<?php

namespace Tests;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use PHPUnit\Framework\Attributes\Test;
use Stackkit\LaravelDatabaseEmails\Attachment;
use Stackkit\LaravelDatabaseEmails\Email;

class MailableReaderTest extends TestCase
{
    private function mailable(): Mailable
    {
        return new TestMailable();
    }

    #[Test]
    public function it_extracts_the_recipient()
    {
        $composer = Email::compose()
            ->mailable($this->mailable());

        $this->assertEquals(['john@doe.com' => 'John Doe'], $composer->getEmail()->recipient);

        $composer = Email::compose()
            ->mailable(
                $this->mailable()->to(['jane@doe.com'])
            );

        $this->assertCount(2, $composer->getEmail()->recipient);
        $this->assertArrayHasKey('john@doe.com', $composer->getEmail()->recipient);
        $this->assertArrayHasKey('jane@doe.com', $composer->getEmail()->recipient);
    }

    #[Test]
    public function it_extracts_cc_addresses()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals(['john+cc@doe.com' => null, 'john+cc2@doe.com' => null], $composer->getEmail()->cc);
    }

    #[Test]
    public function it_extracts_bcc_addresses()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals(['john+bcc@doe.com' => null, 'john+bcc2@doe.com' => null], $composer->getEmail()->bcc);
    }

    #[Test]
    public function it_extracts_reply_to_addresses()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals(['replyto@example.com' => null, 'replyto2@example.com' => null], $composer->getEmail()->reply_to);
    }

    #[Test]
    public function it_extracts_the_subject()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals('Your order has shipped!', $composer->getEmail()->subject);
    }

    #[Test]
    public function it_extracts_the_body()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals("Name: John Doe\n", $composer->getEmail()->body);
    }

    #[Test]
    public function it_extracts_attachments()
    {
        $email = Email::compose()->mailable($this->mailable())->send();

        $attachments = $email->attachments;

        $this->assertCount(2, $attachments);

        $this->assertEquals(__DIR__.'/files/pdf-sample.pdf', $attachments[0]['path']);
    }

    #[Test]
    public function it_extracts_the_from_address_and_or_name()
    {
        $email = Email::compose()->mailable(
            ($this->mailable())
                ->from('marick@dolphiq.nl', 'Marick')
        )->send();

        $this->assertTrue((bool) $email->from);
        $this->assertEquals('marick@dolphiq.nl', $email->from['address']);
        $this->assertEquals('Marick', $email->from['name']);

        $email = Email::compose()->mailable(
            ($this->mailable())
                ->from('marick@dolphiq.nl')
        )->send();

        $this->assertTrue((bool) $email->from);
        $this->assertEquals('marick@dolphiq.nl', $email->from['address']);
        $this->assertEquals(null, $email->from['name']);

        $email = Email::compose()->mailable(
            ($this->mailable())
                ->from('marick@dolphiq.nl', 'Marick')
        )->send();

        $this->assertEquals('marick@dolphiq.nl', $email->from['address']);
        $this->assertEquals('Marick', $email->from['name']);
    }
}

class TestMailable extends Mailable
{
    public function content(): Content
    {
        $content = new Content(
            'tests::dummy'
        );

        $content->with('name', 'John Doe');

        return $content;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            null,
            [
                new Address('john@doe.com', 'John Doe'),
            ],
            ['john+cc@doe.com', 'john+cc2@doe.com'],
            ['john+bcc@doe.com', 'john+bcc2@doe.com'],
            ['replyto@example.com', new Address('replyto2@example.com')],
            'Your order has shipped!'
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath(__DIR__.'/files/pdf-sample.pdf')->withMime('application/pdf'),
            Attachment::fromStorageDisk(__DIR__.'/files/pdf-sample.pdf', 'my-local-disk')->withMime('application/pdf'),
        ];
    }
}
