<?php

namespace Pterodactyl\Rules;

use Illuminate\Contracts\Validation\Rule;

class Username implements Rule
{
    
    public const VALIDATION_REGEX = '/^[a-z0-9]([\w\.-]+)[a-z0-9]$/';

    
    public function passes($attribute, $value): bool
    {
        return preg_match(self::VALIDATION_REGEX, mb_strtolower($value));
    }

    
    public function message(): string
    {
        return 'The :attribute must start and end with alpha-numeric characters and
                contain only letters, numbers, dashes, underscores, and periods.';
    }

    
    public function __toString(): string
    {
        return 'p_username';
    }
}
