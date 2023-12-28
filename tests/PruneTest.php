<?php

namespace Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Stackkit\LaravelDatabaseEmails\Email;

class PruneTest extends TestCase
{
    /** @test */
    public function by_default_mails_are_pruned_after_6_months()
    {
        $email = $this->sendEmail();

        Carbon::setTestNow($email->created_at . ' + 6 months');
        $this->artisan('model:prune', ['--model' => [Email::class]]);
        $this->assertInstanceOf(Email::class, $email->fresh());

        Carbon::setTestNow($email->created_at . ' + 6 months + 1 day');

        // Ensure the email object has to be passed manually, otherwise we are acidentally
        // deleting everyone's e-mails...
        $this->artisan('model:prune');
        $this->assertInstanceOf(Email::class, $email->fresh());

        // Now test with it passed... then it should definitely be deleted.
        $this->artisan('model:prune', ['--model' => [Email::class]]);
        $this->assertNull($email->fresh());
    }

    /** @test */
    public function can_change_when_emails_are_pruned()
    {
        Email::pruneWhen(function (Email $email) {
            return $email->where('created_at', '<', now()->subMonths(3));
        });

        $email = $this->sendEmail();

        Carbon::setTestNow($email->created_at . ' + 3 months');
        $this->artisan('model:prune', ['--model' => [Email::class]]);
        $this->assertInstanceOf(Email::class, $email->fresh());

        Carbon::setTestNow($email->created_at . ' + 3 months + 1 day');
        $this->artisan('model:prune', ['--model' => [Email::class]]);
        $this->assertNull($email->fresh());
    }
}
