<?php

declare(strict_types=1);

namespace Workbench\App\Models;

class UserWithPreferredEmail extends User
{
    public function preferredEmailAddress(): string
    {
        return 'noreply@abc.com';
    }
}
