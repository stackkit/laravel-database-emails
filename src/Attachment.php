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

    public static function fromPath(string $path): self
    {
        return new static($path);
    }

    public static function fromData(): void
    {
        throw new RuntimeException('Raw attachments are not supported in the database email driver.');
    }

    public static function fromStorage(): void
    {
        throw new RuntimeException('Raw attachments are not supported in the database email driver.');
    }

    public static function fromStorageDisk($disk, $path): self
    {
        return new static($path, $disk);
    }

    public function as(string $name): self
    {
        $this->as = $name;

        return $this;
    }

    public function withMime(string $mime): self
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
