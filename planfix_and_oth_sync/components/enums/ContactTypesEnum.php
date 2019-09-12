<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 05.02.18
 * Time: 20:51
 */

namespace app\components\enums;


class ContactTypesEnum extends AbstractEnum
{
    const TYPE_AFFILIATE = 'affiliate';
    const TYPE_ADVERTISER = 'advertiser';
    const TYPE_MANAGER = 'manager';

    public function getValues()
    {
        return [
            self::TYPE_AFFILIATE,
            self::TYPE_ADVERTISER,
            self::TYPE_MANAGER
        ];
    }

    public function getClientValues()
    {
        return [
            self::TYPE_AFFILIATE => 'вембастер',
            self::TYPE_ADVERTISER => 'рекламодатель',
            self::TYPE_MANAGER => 'manager'
        ];
    }
}