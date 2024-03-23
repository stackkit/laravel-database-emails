<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Stackkit\LaravelDatabaseEmails\Attachment;

class DatabaseInteractionTest extends TestCase
{
    #[Test]
    public function label_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['label' => 'welcome-email']);

        $this->assertEquals('welcome-email', DB::table('emails')->find(1)->label);
        $this->assertEquals('welcome-email', $email->label);
    }

    #[Test]
    public function recipient_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['recipient' => 'john@doe.com']);

        $this->assertEquals(['john@doe.com' => null], $email->recipient);
    }

    #[Test]
    public function cc_and_bcc_should_be_saved_correctly()
    {
        $email = $this->sendEmail([
            'cc' => $cc = [
                'john@doe.com',
            ],
            'bcc' => $bcc = [
                'jane@doe.com',
            ],
        ]);

        $this->assertEquals(['john@doe.com' => null], $email->cc);
        $this->assertEquals(['jane@doe.com' => null], $email->bcc);
    }

    #[Test]
    public function reply_to_should_be_saved_correctly()
    {
        $email = $this->sendEmail([
            'reply_to' => [
                'john@doe.com',
            ],
        ]);

        $this->assertEquals(['john@doe.com' => null], $email->reply_to);
    }

    #[Test]
    public function subject_should_be_saved_correclty()
    {
        $email = $this->sendEmail(['subject' => 'test subject']);

        $this->assertEquals('test subject', DB::table('emails')->find(1)->subject);
        $this->assertEquals('test subject', $email->subject);
    }

    #[Test]
    public function view_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['view' => 'tests::dummy']);

        $this->assertEquals('tests::dummy', DB::table('emails')->find(1)->view);
        $this->assertEquals('tests::dummy', $email->view);
    }

    #[Test]
    public function scheduled_date_should_be_saved_correctly()
    {
        $email = $this->sendEmail();
        $this->assertNull(DB::table('emails')->find(1)->scheduled_at);
        $this->assertNull($email->scheduled_at);

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 1, 2, 3));
        $email = $this->scheduleEmail('+2 weeks');
        $this->assertNotNull(DB::table('emails')->find(2)->scheduled_at);
        $this->assertEquals('2019-01-15 01:02:03', $email->scheduled_at);
    }

    #[Test]
    public function the_body_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'Jane Doe']]);

        $expectedBody = "Name: Jane Doe\n";

        $this->assertSame($expectedBody, DB::table('emails')->find(1)->body);
        $this->assertSame($expectedBody, $email->body);
    }

    #[Test]
    public function from_should_be_saved_correctly()
    {
        $email = $this->composeEmail()->send();

        $this->assertEquals($email->from['address'], $email->from['address']);
        $this->assertEquals($email->from['name'], $email->from['name']);

        $email = $this->composeEmail([
            'from' => new Address('marick@dolphiq.nl', 'Marick'),
        ])->send();

        $this->assertTrue((bool) $email->from);
        $this->assertEquals('marick@dolphiq.nl', $email->from['address']);
        $this->assertEquals('Marick', $email->from['name']);
    }

    #[Test]
    public function variables_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'John Doe']]);

        $this->assertEquals(['name' => 'John Doe'], $email->variables);
    }

    #[Test]
    public function the_sent_date_should_be_null()
    {
        $email = $this->sendEmail();

        $this->assertNull(DB::table('emails')->find(1)->sent_at);
        $this->assertNull($email->sent_at);
    }

    #[Test]
    public function failed_should_be_zero()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->failed);
        $this->assertFalse($email->hasFailed());
    }

    #[Test]
    public function attempts_should_be_zero()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->attempts);
        $this->assertEquals(0, $email->attempts);
    }

    #[Test]
    public function the_scheduled_date_should_be_saved_correctly()
    {
        Carbon::setTestNow(Carbon::now());

        $scheduledFor = date('Y-m-d H:i:s', Carbon::now()->addWeek(2)->timestamp);

        $email = $this->scheduleEmail('+2 weeks');

        $this->assertEquals($scheduledFor, $email->scheduled_at);
    }

    #[Test]
    public function recipient_should_be_swapped_for_test_address_when_in_testing_mode()
    {
        $this->app['config']->set('database-emails.testing.enabled', function () {
            return true;
        });
        $this->app['config']->set('database-emails.testing.email', 'test@address.com');

        $email = $this->sendEmail(['recipient' => 'jane@doe.com']);

        $this->assertEquals(['test@address.com' => null], $email->recipient);
    }

    #[Test]
    public function attachments_should_be_saved_correctly()
    {
        $email = $this->composeEmail()
            ->attachments([
                Attachment::fromPath(__DIR__.'/files/pdf-sample.pdf'),
                Attachment::fromPath(__DIR__.'/files/pdf-sample2.pdf'),
                Attachment::fromStorageDisk('my-custom-disk', 'pdf-sample-2.pdf'),
            ])
            ->send();

        $this->assertCount(3, $email->attachments);

        $this->assertEquals(
            [
                'path' => __DIR__.'/files/pdf-sample.pdf',
                'disk' => null,
                'as' => null,
                'mime' => null,
            ],
            $email->attachments[0]
        );
    }

    #[Test]
    public function in_memory_attachments_are_not_supported()
    {
        $this->expectExceptionMessage('Raw attachments are not supported in the database email driver.');

        $this->composeEmail()
            ->attachments([
                Attachment::fromData(fn () => file_get_contents(__DIR__.'/files/pdf-sample.pdf'), 'pdf-sample'),
            ])
            ->send();
    }
}
