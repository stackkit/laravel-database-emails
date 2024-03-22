<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Contracts\Translation\HasLocalePreference;

class UserWithPreferredLocale extends User implements HasLocalePreference
{
    public function preferredLocale(): string
    {
        return 'fil-PH';
    }
}
