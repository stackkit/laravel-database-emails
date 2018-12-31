<?php

namespace Stackkit\LaravelDatabaseEmails;

use Exception;
use Carbon\Carbon;
use InvalidArgumentException;

class Validator
{
    /**
     * The e-mail composer.
     *
     * @var EmailComposer
     */
    protected $composer;

    /**
     * Validate the data that was given to the e-mail composer.
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    public function validate(EmailComposer $composer)
    {
        $this->validateLabel($composer);

        $this->validateRecipient($composer);

        $this->validateCc($composer);

        $this->validateBcc($composer);

        $this->validateSubject($composer);

        $this->validateView($composer);

        $this->validateVariables($composer);

        $this->validateScheduled($composer);
    }

    /**
     * Validate the defined label.
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    private function validateLabel(EmailComposer $composer)
    {
        if ($composer->hasData('label') && strlen($composer->getData('label')) > 255) {
            throw new InvalidArgumentException('The given label [' . $composer->getData('label') . '] is too large for database storage');
        }
    }

    /**
     * Validate the given recipient(s).
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    private function validateRecipient(EmailComposer $composer)
    {
        if (! $composer->hasData('recipient')) {
            throw new InvalidArgumentException('No recipient specified');
        }

        $recipients = (array) $composer->getData('recipient');

        if (count($recipients) == 0) {
            throw new InvalidArgumentException('No recipient specified');
        }

        foreach ($recipients as $recipient) {
            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('E-mail address [' . $recipient . '] is invalid');
            }
        }
    }

    /**
     * Validate the carbon copy e-mail addresses.
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    private function validateCc(EmailComposer $composer)
    {
        if (! $composer->hasData('cc')) {
            return;
        }

        foreach ((array) $composer->getData('cc') as $cc) {
            if (! filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('E-mail address [' . $cc . '] is invalid');
            }
        }
    }

    /**
     * Validate the blind carbon copy e-mail addresses.
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    private function validateBcc(EmailComposer $composer)
    {
        if (! $composer->hasData('bcc')) {
            return;
        }

        foreach ((array) $composer->getData('bcc') as $bcc) {
            if (! filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidargumentException('E-mail address [' . $bcc . '] is invalid');
            }
        }
    }

    /**
     * Validate the e-mail subject.
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    private function validateSubject(EmailComposer $composer)
    {
        if (! $composer->hasData('subject')) {
            throw new InvalidArgumentException('No subject specified');
        }
    }

    /**
     * Validate the e-mail view.
     *
     * @param EmailComposer $composer
     * @throws InvalidARgumentException
     */
    private function validateView(EmailComposer $composer)
    {
        if ($composer->hasData('mailable')) {
            return;
        }

        if (! $composer->hasData('view')) {
            throw new InvalidArgumentException('No view specified');
        }

        $view = $composer->getData('view');

        if (! view()->exists($view)) {
            throw new InvalidArgumentException('View [' . $view . '] does not exist');
        }
    }

    /**
     * Validate the e-mail variables.
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    private function validateVariables(EmailComposer $composer)
    {
        if ($composer->hasData('variables') && ! is_array($composer->getData('variables'))) {
            throw new InvalidArgumentException('Variables must be an array');
        }
    }

    /**
     * Validate the scheduled date.
     *
     * @param EmailComposer $composer
     * @throws InvalidArgumentException
     */
    private function validateScheduled(EmailComposer $composer)
    {
        if (! $composer->hasData('scheduled_at')) {
            return;
        }

        $scheduled = $composer->getData('scheduled_at');

        if (! $scheduled instanceof Carbon && ! is_string($scheduled)) {
            throw new InvalidArgumentException('Scheduled date must be a Carbon\Carbon instance or a strtotime-valid string');
        }

        if (is_string($scheduled)) {
            try {
                Carbon::parse($scheduled);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Scheduled date could not be parsed by Carbon: ' . $e->getMessage());
            }
        }
    }
}
