<?php

namespace app\modules\tickets\helpers;

use DateInterval;
use DateTime;

class CorrectTimeHelper
{
    public static function correctDefaultDateTime(string $strDateTime): DateTime
    {
        return self::correctBeginDateTime($strDateTime, env('DEFAULT_CORRECT_HOURS'));
    }

    public static function correctBeginDateTime(string $strDateTime, int $hours = 0): DateTime
    {
        $dateTime = new DateTime($strDateTime);

        if ($hours > 0) {
            $dateTime->add(new DateInterval('PT' . $hours . 'H'));
        }
        if ($hours < 0) {
            $dateTime->sub(new DateInterval('PT' . abs($hours) . 'H'));
        }

        return $dateTime;
    }
}
