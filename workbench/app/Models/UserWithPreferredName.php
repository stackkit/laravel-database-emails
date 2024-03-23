<?php

declare(strict_types=1);

namespace Workbench\App\Models;

class UserWithPreferredName extends User
{
    public function preferredEmailName(): string
    {
        return 'J.D.';
    }
}
