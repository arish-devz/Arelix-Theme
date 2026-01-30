<?php

namespace Pterodactyl\Helpers;

use Carbon\CarbonImmutable;

final class Time
{
    
    public static function getMySQLTimezoneOffset(string $timezone): string
    {
        return CarbonImmutable::now($timezone)->getTimezone()->toOffsetName();
    }
}
