<?php

namespace app\modules\tickets\enum;

class TicketDimensionEnum
{
    const DIMENSION_AGENCY_CABINET_ID = 'agency_cabinet_id';
    const DIMENSION_EXTERNAL_AMOUNT = 'external_amount';
    const DIMENSION_SOURCE_WALLET = 'source_wallet';
    const DIMENSION_PROTECTION_CODE = 'protection_code';
    const DIMENSION_SOURCE_AGENCY_CABINET_ID = 'source_agency_cabinet_id';
    const DIMENSION_DST_AGENCY_CABINET_ID = 'dst_agency_cabinet_id';
    const DIMENSION_COUNTRY_ID = 'country_id';
    const DIMENSION_ADVERTISING_COMPANY_ID = 'advertising_company_id';
    const DIMENSION_PLATFORM_TYPE = 'platform_type';

    public static function getAll(): array
    {
        return [
            self::DIMENSION_AGENCY_CABINET_ID => 'ID агентского кабинета',
            self::DIMENSION_EXTERNAL_AMOUNT => 'Сумма для пополнения с внешнего источника',
            self::DIMENSION_SOURCE_WALLET => 'Кошелек, скоторого сделан перевод',
            self::DIMENSION_PROTECTION_CODE => 'Код проеткции платежа',
            self::DIMENSION_SOURCE_AGENCY_CABINET_ID => 'Исходный кабинет для перевода',
            self::DIMENSION_DST_AGENCY_CABINET_ID => 'Конечный кабинет для перевода',
            self::DIMENSION_COUNTRY_ID => 'ID Страны',
            self::DIMENSION_ADVERTISING_COMPANY_ID => 'ID Рекламной компании',
            self::DIMENSION_PLATFORM_TYPE => 'Тип платформы',
        ];
    }

    public static function get(string $key): string
    {
        return self::getAll()[$key];
    }
}
