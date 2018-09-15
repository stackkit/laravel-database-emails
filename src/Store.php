<?php

namespace Stackkit\LaravelDatabaseEmails;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class Store
{
    /**
     * Get all queued e-mails.
     *
     * @return Collection|Email[]
     */
    public function getQueue()
    {
        $query = new Email;

        return $query
            ->whereNull('deleted_at')
            ->whereNull('sent_at')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', Carbon::now()->toDateTimeString());
            })
            ->where('sending', '=', 0)
            ->where('attempts', '<', Config::maxAttemptCount())
            ->orderBy('created_at', 'asc')
            ->limit(Config::cronjobEmailLimit())
            ->get();
    }
}
