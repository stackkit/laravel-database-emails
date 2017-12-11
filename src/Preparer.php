<?php

namespace Buildcode\LaravelDatabaseEmails;

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

        $this->prepareCc($composer);

        $this->prepareBcc($composer);

        $this->prepareSubject($composer);

        $this->prepareView($composer);

        $this->prepareVariables($composer);

        $this->prepareBody($composer);

        $this->prepareScheduled($composer);
    }

    /**
     * Prepare the label for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareLabel(EmailComposer $composer)
    {
        if (!$composer->hasData('label')) {
            return;
        }

        $composer->getEmail()->fill([
            'label' => $composer->getData('label')
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
     * Prepare the carbon copies for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareCc(EmailComposer $composer)
    {
        if (Config::testing()) {
            return;
        }

        if (!$composer->hasData('cc')) {
            return;
        }

        $composer->getEmail()->fill([
            'cc' => json_encode($composer->getData('cc')),
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
            return;
        }

        if (!$composer->hasData('bcc')) {
            return;
        }

        $composer->getEmail()->fill([
            'bcc' => json_encode($composer->getData('bcc')),
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
        if (!$composer->hasData('variables')) {
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
        $composer->getEmail()->fill([
            'body' => view(
                $composer->getData('view'),
                $composer->hasData('variables') ? $composer->getData('variables') : []
            )->render(),
        ]);
    }

    /**
     * Prepare the scheduled date for database storage.
     *
     * @param EmailComposer $composer
     */
    private function prepareScheduled(EmailComposer $composer)
    {
        if (!$composer->hasData('scheduled_at')) {
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
}
