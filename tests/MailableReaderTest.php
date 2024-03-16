<?php

namespace Tests;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Stackkit\LaravelDatabaseEmails\Email;

class MailableReaderTest extends TestCase
{
    private function mailable(): Mailable
    {
        return new TestMailable();
    }

    /** @test */
    public function it_extracts_the_recipient()
    {
        $composer = Email::compose()
            ->mailable($this->mailable());

        $this->assertEquals(['john@doe.com'], $composer->getData('recipient'));

        $composer = Email::compose()
            ->mailable(
                $this->mailable()->to(['jane@doe.com'])
            );

        $this->assertCount(2, $composer->getData('recipient'));
        $this->assertContains('john@doe.com', $composer->getData('recipient'));
        $this->assertContains('jane@doe.com', $composer->getData('recipient'));
    }

    /** @test */
    public function it_extracts_cc_addresses()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals(['john+cc@doe.com', 'john+cc2@doe.com'], $composer->getData('cc'));
    }

    /** @test */
    public function it_extracts_bcc_addresses()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals(['john+bcc@doe.com', 'john+bcc2@doe.com'], $composer->getData('bcc'));
    }

    /** @test */
    public function it_extracts_reply_to_addresses()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals(['replyto@example.com', 'replyto2@example.com'], $composer->getData('reply_to'));
    }

    /** @test */
    public function it_extracts_the_subject()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals('Your order has shipped!', $composer->getData('subject'));
    }

    /** @test */
    public function it_extracts_the_body()
    {
        $composer = Email::compose()->mailable($this->mailable());

        $this->assertEquals("Name: John Doe\n", $composer->getData('body'));
    }

    /** @test */
    public function it_extracts_attachments()
    {
        $email = Email::compose()->mailable($this->mailable())->send();

        $attachments = $email->getAttachments();

        $this->assertCount(2, $attachments);

        $this->assertEquals('attachment', $attachments[0]['type']);
        $this->assertEquals(__DIR__ . '/files/pdf-sample.pdf', $attachments[0]['attachment']['file']);

        $this->assertEquals('rawAttachment', $attachments[1]['type']);
        $this->assertEquals('order.html', $attachments[1]['attachment']['name']);
        $this->assertEquals('<p>Thanks for your oder</p>', $attachments[1]['attachment']['data']);
    }

    /** @test */
    public function it_extracts_the_from_address_and_or_name()
    {
        $email = Email::compose()->mailable(
            ($this->mailable())
                ->from('marick@dolphiq.nl', 'Marick')
        )->send();

        $this->assertTrue($email->hasFrom());
        $this->assertEquals('marick@dolphiq.nl', $email->getFromAddress());
        $this->assertEquals('Marick', $email->getFromName());

        $email = Email::compose()->mailable(
            ($this->mailable())
                ->from('marick@dolphiq.nl')
        )->send();

        $this->assertTrue($email->hasFrom());
        $this->assertEquals('marick@dolphiq.nl', $email->getFromAddress());
        $this->assertEquals(config('mail.from.name'), $email->getFromName());

        $email = Email::compose()->mailable(
            ($this->mailable())
                ->from(null, 'Marick')
        )->send();

        $this->assertFalse($email->hasFrom());
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
                new Address('john@doe.com', 'John Doe')
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
            Attachment::fromPath(__DIR__ . '/files/pdf-sample.pdf')->withMime('application/pdf'),
            Attachment::fromData(function () {
                return '<p>Thanks for your oder</p>';
            }, 'order.html')
        ];
    }
}
