<?php

namespace Workbench\App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Stackkit\LaravelDatabaseEmails\SendEmailJob;

class CustomSendEmailJob extends SendEmailJob implements ShouldQueue
{
    // Define custom retries, backoff, etc...
}
