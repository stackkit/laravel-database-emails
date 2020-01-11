<?php

namespace Stackkit\LaravelDatabaseEmails;

use Illuminate\Contracts\Encryption\DecryptException;

trait HasEncryptedAttributes
{
    /**
     * The attributes that are encrypted when e-mail encryption is enabled.
     *
     * @var array
     */
    private $encrypted = [
        'recipient',
        'from',
        'cc',
        'bcc',
        'subject',
        'variables',
        'body',
    ];

    /**
     * The attributes that are stored encoded.
     *
     * @var array
     */
    private $encoded = [
        'recipient',
        'from',
        'cc',
        'bcc',
        'variables',
    ];

    /**
     * Get an attribute from the model.
     *
     * @param  string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = $this->attributes[$key];

        if ($this->isEncrypted() && in_array($key, $this->encrypted)) {
            try {
                $value = decrypt($value);
            } catch (DecryptException $e) {
                $value = '';
            }
        }

        if (in_array($key, $this->encoded) && is_string($value)) {
            $decoded = json_decode($value, true);

            if (! is_null($decoded)) {
                $value = $decoded;
            }
        }

        // BC fix for attachments in 4.1.0 and lower.
        // Attachments were stored json encoded.
        // Because this doesn't work for raw attachments, value is now serialized.
        // Check if value is json encoded or serialized, and decode or unserialize accordingly.
        if ($key == 'attachments') {
            if (substr($value, 0, 2) === 'a:') {
                $unserialized = @unserialize($value);
                if ($value !== false) {
                    $value = $unserialized;
                }
            } else {
                $decoded = json_decode($value, true);

                if (! is_null($decoded)) {
                    $value = $decoded;
                }
            }
        }

        return $value;
    }
}
