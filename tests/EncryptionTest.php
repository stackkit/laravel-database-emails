<?php

namespace Tests;

class EncryptionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']['laravel-database-emails.encrypt'] = true;

        $this->sendEmail();
    }

    /** @test */
    public function an_email_should_be_marked_as_encrypted()
    {
        $email = $this->sendEmail();

        $this->assertTrue($email->isEncrypted());
    }

    /** @test */
    public function the_recipient_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['recipient' => 'john@doe.com']);

        $this->assertEquals('john@doe.com', decrypt($email->getOriginal('recipient')));

        $this->assertEquals('john@doe.com', $email->getRecipient());
    }

    /** @test */
    public function cc_and_bb_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail([
            'cc'  => $cc = ['john+1@doe.com', 'john+2@doe.com'],
            'bcc' => $bcc = ['jane+1@doe.com', 'jane+2@doe.com'],
        ]);

        $this->assertEquals($cc, decrypt($email->getOriginal('cc')));
        $this->assertEquals($bcc, decrypt($email->getOriginal('bcc')));

        $this->assertEquals($cc, $email->getCc());
        $this->assertEquals($bcc, $email->getBcc());
    }

    /** @test */
    public function the_subject_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['subject' => 'test subject']);

        $this->assertEquals('test subject', decrypt($email->getOriginal('subject')));

        $this->assertEquals('test subject', $email->getSubject());
    }

    /** @test */
    public function the_variables_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'Jane Doe']]);

        $this->assertEquals(
            ['name' => 'Jane Doe'],
            decrypt($email->getOriginal('variables'))
        );

        $this->assertEquals(
            ['name' => 'Jane Doe'],
            $email->getVariables()
        );
    }

    /** @test */
    public function the_body_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'Jane Doe']]);

        $expectedBody = "Name: Jane Doe\n";

        $this->assertEquals($expectedBody, decrypt($email->getOriginal('body')));

        $this->assertEquals($expectedBody, $email->getBody());
    }

    /** @test */
    public function from_should_be_encrypted_and_decrypted()
    {
        $email = $this->composeEmail()->from('marick@dolphiq.nl', 'Marick')->send();

        $expect = [
            'address' => 'marick@dolphiq.nl',
            'name'    => 'Marick',
        ];

        $this->assertEquals($expect, decrypt($email->getOriginal('from')));
        $this->assertEquals($expect, $email->getFrom());
    }
}
