<?php

namespace app\modules\tickets\enum;

/**
 * Class TicketCategoryEnum
 * @package app\modules\tickets\enum
 */
class TicketCategoryEnum
{
    /** @const int */
    const CATEGORY_AK = 1;

    /** @const int */
    const CATEGORY_TP = 2;

    /** @const int */
    const CATEGORY_PAY = 3;

    /**
     * @return array
     */
    public static function getAll(): array
    {
        return [
            self::CATEGORY_AK => 'Агентский кабинет',
            self::CATEGORY_TP => 'Техническая поддержка',
            self::CATEGORY_PAY => 'Выплаты',
        ];
    }

    /**
     * @return array
     */
    public static function getCategoriesWithWorkerUser(): array
    {
        return [
            self::CATEGORY_TP
        ];
    }
}
