<?php

namespace Tests;

use Buildcode\LaravelDatabaseEmails\Email;
use Illuminate\Mail\Mailable;

class MailableReaderTest extends TestCase
{
    /** @test */
    function it_extracts_the_recipient()
    {
        $composer = Email::compose()
            ->mailable(new TestMailable());

        $this->assertEquals(['john@doe.com'], $composer->getData('recipient'));

        $composer = Email::compose()
            ->mailable(
                (new TestMailable())->to(['jane@doe.com'])
            );

        $this->assertEquals(['john@doe.com', 'jane@doe.com'], $composer->getData('recipient'));
    }

    /** @test */
    function it_extracts_cc_addresses()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals(['john+cc@doe.com', 'john+cc2@doe.com'], $composer->getData('cc'));
    }

    /** @test */
    function it_extracts_bcc_addresses()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals(['john+bcc@doe.com', 'john+bcc2@doe.com'], $composer->getData('bcc'));
    }

    /** @test */
    function it_extracts_the_subject()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals('Your order has shipped!', $composer->getData('subject'));
    }

    /** @test */
    function it_extracts_the_body()
    {
        $composer = Email::compose()->mailable(new TestMailable());

        $this->assertEquals("Name: John Doe\n", $composer->getData('body'));
    }
}

class TestMailable extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->to('john@doe.com')
            ->cc(['john+cc@doe.com', 'john+cc2@doe.com'])
            ->bcc(['john+bcc@doe.com', 'john+bcc2@doe.com'])
            ->subject('Your order has shipped!');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('tests::dummy', ['name' => 'John Doe']);
    }
}
