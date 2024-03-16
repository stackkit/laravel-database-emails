<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Throwable;

class ConfigCacheTest extends TestCase
{
    #[Test]
    public function the_configuration_file_can_be_cached()
    {
        $failed = false;

        try {
            serialize(require __DIR__ . '/../config/laravel-database-emails.php');
        } catch (Throwable) {
            $failed = true;
        }

        if ($failed) {
            $this->fail('Configuration file cannot be serialized');
        } else {
            $this->assertTrue(true);
        }
    }
}
