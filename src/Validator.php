<?php

namespace Buildcode\LaravelDatabaseEmails;

use Carbon\Carbon;
use InvalidArgumentException;
use Exception;

class Validator
{
    private static $email;

    /**
     * Validate the given e-mail.
     *
     * @param Email $email
     * @throws InvalidArgumentException
     */
    public static function validate(Email $email)
    {
        self::$email = $email;

        self::validateRecipient();
        self::validateSubject();
        self::validateView();
        self::validateVariables();
        self::validateScheduled();
    }

    /**
     * Validate the recipient.
     *
     * @throws InvalidArgumentException
     */
    private static function validateRecipient()
    {
        if (strlen(self::$email->getRecipient()) == 0) {
            throw new InvalidArgumentException('No recipient specified');
        }

        if (!filter_var(self::$email->getRecipient(), FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('No valid e-mail specified');
        }
    }

    /**
     * Validate the subject.
     *
     * @throws InvalidArgumentException
     */
    private static function validateSubject()
    {
        if (strlen(self::$email->getSubject()) == 0) {
            throw new InvalidArgumentException('No subject specified');
        }
    }

    /**
     * Validate the view.
     *
     * @throws InvalidArgumentException
     */
    private static function validateView()
    {
        if (strlen(self::$email->getView()) == 0) {
            throw new InvalidArgumentException('No view specified');
        }

        if (!view(self::$email->getView())) {
            throw new InvalidArgumentException('View [' . self::$email->getView() . '] does not exist');
        }
    }

    /**
     * Validate the variables.
     *
     * @throws InvalidArgumentException
     */
    private static function validateVariables()
    {
        if (!is_array(self::$email->getVariables())) {
            throw new InvalidArgumentException('Variables must be an array');
        }
    }

    /**
     * Validate the scheduled date.
     *
     * @throws InvalidArgumentException
     */
    private static function validateScheduled()
    {
        $date = self::$email->getScheduledDate();

        if (is_null($date)) {
            return;
        }

        if (!$date instanceof Carbon && !is_string($date)) {
            throw new InvalidArgumentException('Scheduled date must be a Carbon\Carbon instance or a strtotime-valid string');
        }

        if (is_string($date)) {
            try {
                Carbon::parse($date);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Scheduled date could not be parsed by Carbon: ' . $e->getMessage());
            }
        }
    }
}