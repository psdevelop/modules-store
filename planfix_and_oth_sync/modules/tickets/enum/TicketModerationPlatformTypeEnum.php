<?php

namespace app\modules\tickets\enum;

class TicketModerationPlatformTypeEnum
{
    /** @var string */
    const TYPE_SHOWCASE = 'showcase';

    /** @var string */
    const TYPE_OFFER = 'offer';

    /** @var string */
    const TYPE_OTHER = 'other';

    /** @return string[] */
    public static function getAll()
    {
        return [
            self::TYPE_SHOWCASE => 'Витрина',
            self::TYPE_OFFER => 'Оффер',
            self::TYPE_OTHER => 'Другое',
        ];
    }

    public static function get(string $key): string
    {
        return self::getAll()[$key];
    }
}
