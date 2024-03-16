<?php

namespace Tests;

use Illuminate\Mail\Mailables\Address;
use PHPUnit\Framework\Attributes\Test;

class EncryptionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']['laravel-database-emails.encrypt'] = true;

        $this->sendEmail();
    }

    #[Test]
    public function an_email_should_be_marked_as_encrypted()
    {
        $email = $this->sendEmail();

        $this->assertTrue($email->isEncrypted());
    }

    #[Test]
    public function the_recipient_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['recipient' => 'john@doe.com']);

        $this->assertEquals('john@doe.com', decrypt($email->getRawDatabaseValue('recipient')));

        $this->assertEquals('john@doe.com', $email->getRecipient());
    }

    #[Test]
    public function cc_and_bb_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail([
            'cc'  => $cc = ['john+1@doe.com', 'john+2@doe.com'],
            'bcc' => $bcc = ['jane+1@doe.com', 'jane+2@doe.com'],
        ]);

        $this->assertEquals($cc, decrypt($email->getRawDatabaseValue('cc')));
        $this->assertEquals($bcc, decrypt($email->getRawDatabaseValue('bcc')));

        $this->assertEquals($cc, $email->getCc());
        $this->assertEquals($bcc, $email->getBcc());
    }

    #[Test]
    public function reply_to_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail([
            'reply_to'  => $replyTo = ['john+1@doe.com', 'john+2@doe.com'],
        ]);
        $this->assertEquals($replyTo, decrypt($email->getRawDatabaseValue('reply_to')));
        $this->assertEquals($replyTo, $email->getReplyTo());

        if (! class_exists(Address::class)) {
            return;
        }

        // Test with a single Address object...
        $email = $this->sendEmail([
            'reply_to'  => new Address('john+1@doe.com', 'John Doe'),
        ]);
        $this->assertEquals([['address' => 'john+1@doe.com', 'name' => 'John Doe']], decrypt($email->getRawDatabaseValue('reply_to')));
        $this->assertEquals([['address' => 'john+1@doe.com', 'name' => 'John Doe']], $email->getReplyTo());

        // Address with an array of Address objects...
        $email = $this->sendEmail([
            'reply_to'  => [
                new Address('john+1@doe.com', 'John Doe'),
                new Address('jane+1@doe.com', 'Jane Doe'),
            ],
        ]);
        $this->assertSame([['address' => 'john+1@doe.com', 'name' => 'John Doe'], ['address' => 'jane+1@doe.com', 'name' => 'Jane Doe']], decrypt($email->getRawDatabaseValue('reply_to')));
        $this->assertSame([['address' => 'john+1@doe.com', 'name' => 'John Doe'], ['address' => 'jane+1@doe.com', 'name' => 'Jane Doe']], $email->getReplyTo());
    }

    #[Test]
    public function the_subject_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['subject' => 'test subject']);

        $this->assertEquals('test subject', decrypt($email->getRawDatabaseValue('subject')));

        $this->assertEquals('test subject', $email->getSubject());
    }

    #[Test]
    public function the_variables_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'Jane Doe']]);

        $this->assertEquals(
            ['name' => 'Jane Doe'],
            decrypt($email->getRawDatabaseValue('variables'))
        );

        $this->assertEquals(
            ['name' => 'Jane Doe'],
            $email->getVariables()
        );
    }

    #[Test]
    public function the_body_should_be_encrypted_and_decrypted()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'Jane Doe']]);

        $expectedBody = "Name: Jane Doe\n";

        $this->assertEquals($expectedBody, decrypt($email->getRawDatabaseValue('body')));

        $this->assertEquals($expectedBody, $email->getBody());
    }

    #[Test]
    public function from_should_be_encrypted_and_decrypted()
    {
        $email = $this->composeEmail()->from('marick@dolphiq.nl', 'Marick')->send();

        $expect = [
            'address' => 'marick@dolphiq.nl',
            'name'    => 'Marick',
        ];

        $this->assertEquals($expect, decrypt($email->getRawDatabaseValue('from')));
        $this->assertEquals($expect, $email->getFrom());
    }
}
