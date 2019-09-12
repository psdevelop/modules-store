<?php

namespace app\modules\tickets\enum;

class TicketCategorySubEnum
{
    /** @var int */
    const SUB_REPLENISHMENT = 1;

    /** @var int */
    const SUB_CREATE = 2;

    /** @var int */
    const SUB_TRANSFER = 3;

    /** @var int */
    const SUB_OTHER = 4;

    /** @var int */
    const SUB_MODERATION = 5;

    /** @var int */
    const SUB_FREE_FORM = 10;

    public static function getAll(): array
    {
        return [
            self::SUB_REPLENISHMENT => 'Пополнение',
            self::SUB_CREATE => 'Создание',
            self::SUB_TRANSFER => 'Перевод',
            self::SUB_OTHER => 'Прочие',
            self::SUB_MODERATION => 'Модерация',
            self::SUB_FREE_FORM => 'Обращение в свободной форме',
        ];
    }
}
