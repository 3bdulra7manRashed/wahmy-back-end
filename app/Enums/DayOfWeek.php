<?php

declare(strict_types=1);

namespace App\Enums;

enum DayOfWeek: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    /**
     * Get the current day of the week.
     */
    public static function today(): self
    {
        return self::from((int) now()->dayOfWeek);
    }
}
