<?php
namespace app\components\enums;
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 05.02.18
 * Time: 12:34
 */
class EntityNamesEnum extends AbstractEnum
{
    const OFFER_TASK = 'offerTask';
    const ACHIEVE_TASK = 'achieveTask';

    public function getValues()
    {
        return [
            self::ACHIEVE_TASK,
            self::OFFER_TASK
        ];
    }

    public function getClientValues()
    {
        return [
            self::ACHIEVE_TASK => 'Достижения',
            self::OFFER_TASK => 'Задача по офферу (подлкючение трекеингового домена)'
        ];
    }

}