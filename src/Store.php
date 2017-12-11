<?php

namespace Buildcode\LaravelDatabaseEmails;

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

    /**
     * Get all e-mails that failed to be sent.
     *
     * @param int $id
     * @return Collection
     */
    public function getFailed($id = null)
    {
        $query = new Email;

        return $query
            ->when($id, function ($query) use ($id) {
                $query->where('id', '=', $id);
            })
            ->where('failed', '=', 1)
            ->where('attempts', '>=', Config::maxAttemptCount())
            ->whereNull('sent_at')
            ->whereNull('deleted_at')
            ->get();
    }
}
