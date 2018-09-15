<?php

namespace Tests;

use Carbon;
use InvalidArgumentException;
use Stackkit\LaravelDatabaseEmails\Email;

class ValidatorTest extends TestCase
{
    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function a_label_cannot_contain_more_than_255_characters()
    {
        Email::compose()
            ->label(str_repeat('a', 256))
            ->send();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No recipient specified
     */
    public function a_recipient_is_required()
    {
        Email::compose()
            ->send();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage E-mail address [not-a-valid-email-address] is invalid
     */
    public function the_recipient_email_must_be_valid()
    {
        Email::compose()
            ->recipient('not-a-valid-email-address')
            ->send();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage E-mail address [not-a-valid-email-address] is invalid
     */
    public function cc_must_contain_valid_email_addresses()
    {
        Email::compose()
            ->recipient('john@doe.com')
            ->cc([
                'jane@doe.com',
                'not-a-valid-email-address',
            ])
            ->send();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage E-mail address [not-a-valid-email-address] is invalid
     */
    public function bcc_must_contain_valid_email_addresses()
    {
        Email::compose()
            ->recipient('john@doe.com')
            ->bcc([
                'jane@doe.com',
                'not-a-valid-email-address',
            ])
            ->send();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No subject specified
     */
    public function a_subject_is_required()
    {
        Email::compose()
            ->recipient('john@doe.com')
            ->send();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage No view specified
     */
    public function a_view_is_required()
    {
        Email::compose()
            ->recipient('john@doe.com')
            ->subject('test')
            ->send();
    }

    /** @test */
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

    /** @test */
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
            } catch (InvalidArgumentException $e) {
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

    /** @test */
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
