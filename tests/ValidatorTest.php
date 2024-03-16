<?php

namespace Tests;

use Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Stackkit\LaravelDatabaseEmails\Email;

class ValidatorTest extends TestCase
{
    #[Test]
    public function a_label_cannot_contain_more_than_255_characters()
    {
        $this->expectException(InvalidArgumentException::class);

        Email::compose()
            ->label(str_repeat('a', 256))
            ->send();
    }

    #[Test]
    public function a_recipient_is_required()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No recipient specified');

        Email::compose()
            ->send();
    }

    #[Test]
    public function a_recipient_cannot_be_empty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No recipient specified');

        Email::compose()
            ->recipient([])
            ->send();
    }

    #[Test]
    public function the_recipient_email_must_be_valid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('E-mail address [not-a-valid-email-address] is invalid');

        Email::compose()
            ->recipient('not-a-valid-email-address')
            ->send();
    }

    #[Test]
    public function cc_must_contain_valid_email_addresses()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('E-mail address [not-a-valid-email-address] is invalid');

        Email::compose()
            ->recipient('john@doe.com')
            ->cc([
                'jane@doe.com',
                'not-a-valid-email-address',
            ])
            ->send();
    }

    #[Test]
    public function bcc_must_contain_valid_email_addresses()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('E-mail address [not-a-valid-email-address] is invalid');

        Email::compose()
            ->recipient('john@doe.com')
            ->bcc([
                'jane@doe.com',
                'not-a-valid-email-address',
            ])
            ->send();
    }

    #[Test]
    public function reply_to_must_contain_valid_email_addresses()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('E-mail address [not-a-valid-email-address] is invalid');

        Email::compose()
            ->recipient('john@doe.com')
            ->replyTo([
                'jane@doe.com',
                'not-a-valid-email-address',
            ])
            ->send();
    }

    #[Test]
    public function a_subject_is_required()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No subject specified');

        Email::compose()
            ->recipient('john@doe.com')
            ->send();
    }

    #[Test]
    public function a_view_is_required()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No view specified');

        Email::compose()
            ->recipient('john@doe.com')
            ->subject('test')
            ->send();
    }

    #[Test]
    public function the_view_must_exist()
    {
        // this view exists, if error thrown -> fail test
        try {
            Email::compose()
                ->recipient('john@doe.com')
                ->subject('test')
                ->view('tests::dummy')
                ->send();
        } catch (InvalidArgumentException $e) {
            $this->fail('Expected view [tests::dummy] to exist but it does not');
        }

        // this view does not exist -> expect exception
        $this->expectException(InvalidArgumentException::class);

        Email::compose()
            ->recipient('john@doe.com')
            ->subject('test')
            ->view('tests::does-not-exist')
            ->send();
    }

    #[Test]
    public function variables_must_be_defined_as_an_array()
    {
        $email = Email::compose()
            ->recipient('john@doe.com')
            ->subject('test')
            ->view('tests::dummy');

        foreach ($this->invalid as $type) {
            try {
                $email->variables($type)->send();
                $this->fail('Expected exception to be thrown');
            } catch (\TypeError $e) {
                $this->assertEquals($e->getCode(), 0);
            }
        }

        $valid = [];

        try {
            $email->variables($valid)->send();
        } catch (InvalidArgumentException $e) {
            $this->fail('Did not expect exception to be thrown');
        }
    }

    #[Test]
    public function the_scheduled_date_must_be_a_carbon_instance_or_a_valid_date()
    {
        // invalid
        foreach ($this->invalid as $value) {
            try {
                Email::compose()
                    ->recipient('john@doe.com')
                    ->subject('test')
                    ->view('tests::dummy')
                    ->schedule($value);
                $this->fail('Expected exception to be thrown');
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(0, $e->getCode());
            }
        }

        // valid
        try {
            Email::compose()
                ->recipient('john@doe.com')
                ->subject('test')
                ->view('tests::dummy')
                ->schedule('+2 week');

            Email::compose()
                ->recipient('john@doe.com')
                ->subject('test')
                ->view('tests::dummy')
                ->schedule(Carbon\Carbon::now());
        } catch (InvalidArgumentException $e) {
            $this->fail('Dit not expect exception to be thrown');
        }
    }
}
