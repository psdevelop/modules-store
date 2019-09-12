<?php

namespace app\modules\tickets\enum;

class TicketAccountTypeEnum
{
    /** @var string */
    const TYPE_AFFILIATE = 'affiliate';

    /** @var string */
    const TYPE_ADVERTISE = 'advertise';

    /** @var string */
    const TYPE_MANAGER = 'manager';

    private static $mapTypeToTitle = [
        self::TYPE_AFFILIATE => 'Вебмастер',
        self::TYPE_ADVERTISE => 'Рекламодатель',
        self::TYPE_MANAGER => 'Менеджер',
    ];

    public static function getTitleByType(string $type): string
    {
        return self::$mapTypeToTitle[$type];
    }
}
