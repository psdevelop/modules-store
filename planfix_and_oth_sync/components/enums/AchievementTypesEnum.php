<?php
namespace app\components\enums;

class AchievementTypesEnum extends AbstractEnum
{
    const ACHIEVE_INFANT_SCHOOL = 'infant_school';

    /**
     * @return array
     */
    public function getValues()
    {
        return [
            self::ACHIEVE_INFANT_SCHOOL,
        ];
    }

    /**
     * @return array
     */
    public function getClientValues()
    {
        return [
            self::ACHIEVE_INFANT_SCHOOL => 'Приветственный бонус',
        ];
    }

}