<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use TCPDF;

class DatabaseInteractionTest extends TestCase
{
    /** @test */
    public function label_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['label' => 'welcome-email']);

        $this->assertEquals('welcome-email', DB::table('emails')->find(1)->label);
        $this->assertEquals('welcome-email', $email->getLabel());
    }

    /** @test */
    public function recipient_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['recipient' => 'john@doe.com']);

        $this->assertEquals('john@doe.com', $email->getRecipient());
    }

    /** @test */
    public function cc_and_bcc_should_be_saved_correctly()
    {
        $email = $this->sendEmail([
            'cc'  => $cc = [
                'john@doe.com',
            ],
            'bcc' => $bcc = [
                'jane@doe.com',
            ],
        ]);

        $this->assertEquals(json_encode($cc), DB::table('emails')->find(1)->cc);
        $this->assertTrue($email->hasCc());
        $this->assertEquals(['john@doe.com'], $email->getCc());
        $this->assertEquals(json_encode($bcc), DB::table('emails')->find(1)->bcc);
        $this->assertTrue($email->hasBcc());
        $this->assertEquals(['jane@doe.com'], $email->getBcc());
    }

    /** @test */
    public function subject_should_be_saved_correclty()
    {
        $email = $this->sendEmail(['subject' => 'test subject']);

        $this->assertEquals('test subject', DB::table('emails')->find(1)->subject);
        $this->assertEquals('test subject', $email->getSubject());
    }

    /** @test */
    public function view_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['view' => 'tests::dummy']);

        $this->assertEquals('tests::dummy', DB::table('emails')->find(1)->view);
        $this->assertEquals('tests::dummy', $email->getView());
    }

    /** @test */
    public function encrypted_should_be_saved_correctly()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->encrypted);
        $this->assertFalse($email->isEncrypted());

        $this->app['config']['laravel-database-emails.encrypt'] = true;

        $email = $this->sendEmail();

        $this->assertEquals(1, DB::table('emails')->find(2)->encrypted);
        $this->assertTrue($email->isEncrypted());
    }

    /** @test */
    public function scheduled_date_should_be_saved_correctly()
    {
        $email = $this->sendEmail();
        $this->assertNull(DB::table('emails')->find(1)->scheduled_at);
        $this->assertNull($email->getScheduledDate());

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 1, 2, 3));
        $email = $this->scheduleEmail('+2 weeks');
        $this->assertNotNull(DB::table('emails')->find(2)->scheduled_at);
        $this->assertEquals('2019-01-15 01:02:03', $email->getScheduledDate());
    }

    /** @test */
    public function the_body_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'Jane Doe']]);

        $expectedBody = "Name: Jane Doe\n";

        $this->assertSame($expectedBody, DB::table('emails')->find(1)->body);
        $this->assertSame($expectedBody, $email->getBody());
    }

    /** @test */
    public function from_should_be_saved_correctly()
    {
        $email = $this->composeEmail()->send();

        $this->assertFalse($email->hasFrom());
        $this->assertEquals(config('mail.from.address'), $email->getFromAddress());
        $this->assertEquals(config('mail.from.name'), $email->getFromName());

        $email = $this->composeEmail()->from('marick@dolphiq.nl', 'Marick')->send();

        $this->assertTrue($email->hasFrom());
        $this->assertEquals('marick@dolphiq.nl', $email->getFromAddress());
        $this->assertEquals('Marick', $email->getFromName());
    }

    /** @test */
    public function variables_should_be_saved_correctly()
    {
        $email = $this->sendEmail(['variables' => ['name' => 'John Doe']]);

        $this->assertEquals(json_encode(['name' => 'John Doe'], 1), DB::table('emails')->find(1)->variables);
        $this->assertEquals(['name' => 'John Doe'], $email->getVariables());
    }

    /** @test */
    public function the_sent_date_should_be_null()
    {
        $email = $this->sendEmail();

        $this->assertNull(DB::table('emails')->find(1)->sent_at);
        $this->assertNull($email->getSendDate());
    }

    /** @test */
    public function failed_should_be_zero()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->failed);
        $this->assertFalse($email->hasFailed());
    }

    /** @test */
    public function attempts_should_be_zero()
    {
        $email = $this->sendEmail();

        $this->assertEquals(0, DB::table('emails')->find(1)->attempts);
        $this->assertEquals(0, $email->getAttempts());
    }

    /** @test */
    public function the_scheduled_date_should_be_saved_correctly()
    {
        Carbon::setTestNow(Carbon::now());

        $scheduledFor = date('Y-m-d H:i:s', Carbon::now()->addWeek(2)->timestamp);

        $email = $this->scheduleEmail('+2 weeks');

        $this->assertTrue($email->isScheduled());
        $this->assertEquals($scheduledFor, $email->getScheduledDate());
    }

    /** @test */
    public function recipient_should_be_swapped_for_test_address_when_in_testing_mode()
    {
        $this->app['config']->set('laravel-database-emails.testing.enabled', function () {
            return true;
        });
        $this->app['config']->set('laravel-database-emails.testing.email', 'test@address.com');

        $email = $this->sendEmail(['recipient' => 'jane@doe.com']);

        $this->assertEquals('test@address.com', $email->getRecipient());
    }

    /** @test */
    public function attachments_should_be_saved_correctly()
    {
        $email = $this->composeEmail()
            ->attach(__DIR__ . '/files/pdf-sample.pdf')
            ->send();

        $this->assertCount(1, $email->getAttachments());

        $attachment = $email->getAttachments()[0];

        $this->assertEquals('attachment', $attachment['type']);
        $this->assertEquals(__DIR__ . '/files/pdf-sample.pdf', $attachment['attachment']['file']);

        $email = $this->composeEmail()
            ->attach(__DIR__ . '/files/pdf-sample.pdf')
            ->attach(__DIR__ . '/files/pdf-sample-2.pdf')
            ->send();

        $this->assertCount(2, $email->getAttachments());

        $this->assertEquals(__DIR__ . '/files/pdf-sample.pdf', $email->getAttachments()[0]['attachment']['file']);
        $this->assertEquals(__DIR__ . '/files/pdf-sample-2.pdf', $email->getAttachments()[1]['attachment']['file']);
    }

    /** @test */
    public function in_memory_attachments_should_be_saved_correctly()
    {
        $pdf = new TCPDF;
        $pdf->Write(0, 'Hello CI!');

        $rawData = $pdf->Output('generated.pdf', 'S');

        $email = $this->composeEmail()
            ->attachData($rawData, 'generated.pdf', [
                'mime' => 'application/pdf',
            ])
            ->send();

        $this->assertCount(1, $email->getAttachments());

        $this->assertEquals('rawAttachment', $email->getAttachments()[0]['type']);
        $this->assertEquals(md5($rawData), md5($email->getAttachments()[0]['attachment']['data']));
    }
}
