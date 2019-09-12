<?php

namespace app\modules\tickets\enum;

/**
 * Класс Enum статусов для задач в Planfix
 * Class PlanfixTaskStatusEnum
 * @package app\modules\tickets\enum
 */
class PlanfixTaskStatusEnum
{
    /** @var int Новая*/
    const STATUS_NEW = 1;
    /** @var int В работе*/
    const STATUS_IN_PROGRESS = 2;
    /** @var int Завершенная*/
    const STATUS_COMPLETED = 3;
    /** @var int Отклоненная*/
    const STATUS_REFUSED = 5;
    /** @var int Отмененная*/
    const STATUS_CANCELLED = 7;
    /** @var int Новая 2*/
    const STATUS_IN_PROGRESS_2 = 8;
    /** @var int Ручная обработка*/
    const STATUS_MANUAL_PROCESSING = 146;

    /**
     * @return array
     */
    public static function getAll(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_REFUSED,
            self::STATUS_CANCELLED,
            self::STATUS_IN_PROGRESS_2,
            self::STATUS_MANUAL_PROCESSING,
        ];
    }

    /**
     * @return array
     */
    public static function getAllWithTitle(): array
    {
        return [
            self::STATUS_NEW => 'Новая',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_COMPLETED => 'Завершенная',
            self::STATUS_REFUSED => 'Отклоненная',
            self::STATUS_CANCELLED => 'Отмененная',
            self::STATUS_IN_PROGRESS_2 => 'Новая',
            self::STATUS_MANUAL_PROCESSING => 'Ручная обработка',
        ];
    }
}
