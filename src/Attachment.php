<?php

declare(strict_types=1);

namespace Stackkit\LaravelDatabaseEmails;

use RuntimeException;

class Attachment
{
    public ?string $as = null;

    public ?string $mime = null;

    public function __construct(public string $path, public ?string $disk = null)
    {
        //
    }

    public static function fromPath($path)
    {
        return new static($path);
    }

    public static function fromData()
    {
        throw new RuntimeException('Raw attachments are not supported in the database email driver.');
    }

    public static function fromStorage()
    {
        throw new RuntimeException('Raw attachments are not supported in the database email driver.');
    }

    public static function fromStorageDisk($disk, $path)
    {
        return new static($path, $disk);
    }

    public function as(string $name)
    {
        $this->as = $name;

        return $this;
    }

    public function withMime(string $mime)
    {
        $this->mime = $mime;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'disk' => $this->disk,
            'as' => $this->as,
            'mime' => $this->mime,
        ];
    }
}
