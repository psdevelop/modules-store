<?php

namespace app\modules\tickets\enum;

class TicketStatusEnum
{
    /** @var string */
    const STATUS_NEW = 'new';

    /** @var string */
    const STATUS_IN_PROGRESS = 'in_progress';

    /** @var string */
    const STATUS_COMPLETED = 'completed';

    /** @var string */
    const STATUS_CANCELLED = 'cancelled';

    /** @var string */
    const STATUS_MANUAL_PROCESSING = 'manual_processing';

    public static function getAll(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_MANUAL_PROCESSING,
        ];
    }

    public static function getAllWithTitle(): array
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_COMPLETED => 'Выполнен',
            self::STATUS_CANCELLED => 'Отменен',
            self::STATUS_MANUAL_PROCESSING => 'Ручная обработка',
        ];
    }
}
