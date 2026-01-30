<?php

namespace Pterodactyl\Helpers;

use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ViewErrorBag;

class Utilities
{
    
    public static function randomStringWithSpecialCharacters(int $length = 16): string
    {
        $string = str_random($length);
        
        
        try {
            for ($i = 0; $i < random_int(2, 6); ++$i) {
                $character = ['!', '@', '=', '.', '+', '^'][random_int(0, 5)];

                $string = substr_replace($string, $character, random_int(0, $length - 1), 1);
            }
        } catch (\Exception $exception) {
            
            Log::error($exception);
        }

        return $string;
    }

    
    public static function getScheduleNextRunDate(string $minute, string $hour, string $dayOfMonth, string $month, string $dayOfWeek): Carbon
    {
        return Carbon::instance((new CronExpression(
            sprintf('%s %s %s %s %s', $minute, $hour, $dayOfMonth, $month, $dayOfWeek)
        ))->getNextRunDate());
    }

    public static function checked(string $name, mixed $default): string
    {
        $errors = session('errors');

        if (isset($errors) && $errors instanceof ViewErrorBag && $errors->any()) {
            return old($name) ? 'checked' : '';
        }

        return ($default) ? 'checked' : '';
    }
}
