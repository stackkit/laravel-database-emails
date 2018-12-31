<?php

namespace Tests;

use Illuminate\Mail\Mailable;
use Stackkit\LaravelDatabaseEmails\Email;

class MailableReaderTest extends TestCase
{
    /** @test */
    public function it_extracts_the_recipient()
    {
        $composer = Email::compose()
            ->mailable(new TestMailable());

        $this->assertEquals(['john@doe.com'], $composer->getData('recipient'));

        $composer = Email::compose()
            ->mailable(
                (new TestMailable())->to(['jane@doe.com'])
            );

        $this->assertCount(2, $composer->getData('recipient'));
        $this->assertContains('john@doe.com', $composer->getData('recipient'));
        $this->assertContains('jane@doe.com', $composer->getData('recipient'));
    }

    /** @test */
    public function it_extracts_cc_addresses()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals(['john+cc@doe.com', 'john+cc2@doe.com'], $composer->getData('cc'));
    }

    /** @test */
    public function it_extracts_bcc_addresses()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals(['john+bcc@doe.com', 'john+bcc2@doe.com'], $composer->getData('bcc'));
    }

    /** @test */
    public function it_extracts_the_subject()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals('Your order has shipped!', $composer->getData('subject'));
    }

    /** @test */
    public function it_extracts_the_body()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals("Name: John Doe\n", $composer->getData('body'));
    }

    /** @test */
    public function it_extracts_attachments()
    {
        $email = Email::compose()->mailable(new TestMailable())->send();

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
            (new TestMailable())
                ->from('marick@dolphiq.nl', 'Marick')
        )->send();

        $this->assertTrue($email->hasFrom());
        $this->assertEquals('marick@dolphiq.nl', $email->getFromAddress());
        $this->assertEquals('Marick', $email->getFromName());

        $email = Email::compose()->mailable(
            (new TestMailable())
                ->from('marick@dolphiq.nl')
        )->send();

        $this->assertTrue($email->hasFrom());
        $this->assertEquals('marick@dolphiq.nl', $email->getFromAddress());
        $this->assertEquals(config('mail.from.name'), $email->getFromName());

        $email = Email::compose()->mailable(
            (new TestMailable())
                ->from(null, 'Marick')
        )->send();

        $this->assertTrue($email->hasFrom());
        $this->assertEquals(config('mail.from.address'), $email->getFromAddress());
        $this->assertEquals('Marick', $email->getFromName());
    }
}

class TestMailable extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->to('john@doe.com')
            ->cc(['john+cc@doe.com', 'john+cc2@doe.com'])
            ->bcc(['john+bcc@doe.com', 'john+bcc2@doe.com'])
            ->subject('Your order has shipped!')
            ->attach(__DIR__ . '/files/pdf-sample.pdf', [
                'mime' => 'application/pdf',
            ])
            ->attachData('<p>Thanks for your oder</p>', 'order.html')
            ->view('tests::dummy', ['name' => 'John Doe']);
    }
}
