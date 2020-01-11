<?php

namespace Stackkit\LaravelDatabaseEmails;

use Carbon\Carbon;

class Preparer
{
    /**
     * Prepare the given e-mail for database storage.
     *
     * @param EmailComposer $composer
     */
    public function prepare(EmailComposer $composer)
    {
        $this->prepareLabel($composer);

        $this->prepareRecipient($composer);

        $this->prepareFrom($composer);

        $this->prepareCc($composer);

        $this->prepareBcc($composer);

        $this->prepareSubject($composer);

        $this->prepareView($composer);

        $this->prepareVariables($composer);

        $this->prepareBody($composer);

        $this->prepareAttachments($composer);

        $this->prepareScheduled($composer);

        $this->prepareImmediately($composer);
    }

    /**
     * Prepare the label for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareLabel(EmailComposer $composer)
    {
        if (! $composer->hasData('label')) {
            return;
        }

        $composer->getEmail()->fill([
            'label' => $composer->getData('label'),
        ]);
    }

    /**
     * Prepare the recipient for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareRecipient(EmailComposer $composer)
    {
        if (Config::testing()) {
            $composer->recipient(Config::testEmailAddress());
        }

        $composer->getEmail()->fill([
            'recipient' => json_encode($composer->getData('recipient')),
        ]);
    }

    /**
     * Prepare the from values for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareFrom(EmailComposer $composer)
    {
        $composer->getEmail()->fill([
            'from' => json_encode($composer->getData('from', '')),
        ]);
    }

    /**
     * Prepare the carbon copies for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareCc(EmailComposer $composer)
    {
        if (Config::testing()) {
            $composer->setData('cc', []);
        }

        $composer->getEmail()->fill([
            'cc' => json_encode($composer->getData('cc', [])),
        ]);
    }

    /**
     * Prepare the carbon copies for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareBcc(EmailComposer $composer)
    {
        if (Config::testing()) {
            $composer->setData('bcc', []);
        }

        $composer->getEmail()->fill([
            'bcc' => json_encode($composer->getData('bcc', [])),
        ]);
    }

    /**
     * Prepare the subject for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareSubject(EmailComposer $composer)
    {
        $composer->getEmail()->fill([
            'subject' => $composer->getData('subject'),
        ]);
    }

    /**
     * Prepare the view for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareView(EmailComposer $composer)
    {
        $composer->getEmail()->fill([
            'view' => $composer->getData('view'),
        ]);
    }

    /**
     * Prepare the variables for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareVariables(EmailComposer $composer)
    {
        if (! $composer->hasData('variables')) {
            return;
        }

        $composer->getEmail()->fill([
            'variables' => json_encode($composer->getData('variables')),
        ]);
    }

    /**
     * Prepare the e-mail body for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareBody(EmailComposer $composer)
    {
        // If the body was predefined (by for example a mailable), use that.
        if ($composer->hasData('body')) {
            $body = $composer->getData('body');
        } else {
            $body = view(
                $composer->getData('view'),
                $composer->hasData('variables') ? $composer->getData('variables') : []
            )->render();
        }

        $composer->getEmail()->fill(compact('body'));
    }

    /**
     * Prepare the e-mail attachments.
     *
     * @param EmailComposer $composer
     */
    private function prepareAttachments(EmailComposer $composer)
    {
        $attachments = [];

        foreach ((array) $composer->getData('attachments', []) as $attachment) {
            $attachments[] = [
                'type'       => 'attachment',
                'attachment' => $attachment,
            ];
        }

        foreach ((array) $composer->getData('rawAttachments', []) as $rawAttachment) {
            $attachments[] = [
                'type'       => 'rawAttachment',
                'attachment' => $rawAttachment,
            ];
        }

        $composer->getEmail()->fill([
            'attachments' => serialize($attachments),
        ]);
    }

    /**
     * Prepare the scheduled date for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareScheduled(EmailComposer $composer)
    {
        if (! $composer->hasData('scheduled_at')) {
            return;
        }

        $scheduled = $composer->getData('scheduled_at');

        if (is_string($scheduled)) {
            $scheduled = Carbon::parse($scheduled);
        }

        $composer->getEmail()->fill([
            'scheduled_at' => $scheduled->toDateTimeString(),
        ]);
    }

    /**
     * Prepare the e-mail so it can be sent immediately.
     *
     * @param EmailComposer $composer
     */
    private function prepareImmediately(EmailComposer $composer)
    {
        if (Config::sendImmediately()) {
            $composer->getEmail()->fill(['sending' => 1]);
        }
    }
}
