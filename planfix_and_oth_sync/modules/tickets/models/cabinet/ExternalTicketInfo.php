<?php

namespace app\modules\tickets\models\cabinet;

use app\modules\tickets\enum\TicketDimensionEnum;
use app\modules\tickets\enum\TicketModerationPlatformTypeEnum;
use app\modules\tickets\models\BaseModel;

class ExternalTicketInfo extends BaseModel
{
    /** @var string */
    public $countryTitle;

    /** @var string */
    public $platformTitle;

    /** @var string */
    public $externalAmount;

    /** @var string */
    public $sourceWallet;

    /** @var string */
    public $protectionCode;

    /** @var string */
    public $advertisingCompanyId;

    /** @var ExternalCabinetInfo */
    public $cabinetInfo;

    /** @var ExternalCabinetInfo */
    public $cabinetInfoSource;

    /** @var ExternalCabinetInfo */
    public $cabinetInfoDestination;

    /**
     * @param array[] $dimensions
     * @param array[] $agencyCabinetsWithIndex
     * @param array[] $countriesWithIndex
     * @return ExternalTicketInfo
     */
    public static function getInstanceFromArrayDimensions(
        array $dimensions,
        array $agencyCabinetsWithIndex,
        array $countriesWithIndex
    ): ExternalTicketInfo {

        $externalTicketInfo = new ExternalTicketInfo();

        foreach ($dimensions as $dimension) {
            switch ($dimension['code']) {
                case TicketDimensionEnum::DIMENSION_AGENCY_CABINET_ID:
                    $externalTicketInfo->cabinetInfo =
                        ExternalCabinetInfo::getInstanceFromArray($agencyCabinetsWithIndex[$dimension['value']]);
                    break;
                case TicketDimensionEnum::DIMENSION_SOURCE_AGENCY_CABINET_ID:
                    $externalTicketInfo->cabinetInfoSource =
                        ExternalCabinetInfo::getInstanceFromArray($agencyCabinetsWithIndex[$dimension['value']]);
                    break;
                case TicketDimensionEnum::DIMENSION_DST_AGENCY_CABINET_ID:
                    $externalTicketInfo->cabinetInfoDestination =
                        ExternalCabinetInfo::getInstanceFromArray($agencyCabinetsWithIndex[$dimension['value']]);
                    break;
                case TicketDimensionEnum::DIMENSION_COUNTRY_ID:
                    $externalTicketInfo->countryTitle = $countriesWithIndex[$dimension['value']]['name'];
                    break;
                case TicketDimensionEnum::DIMENSION_PLATFORM_TYPE:
                    $externalTicketInfo->platformTitle = TicketModerationPlatformTypeEnum::get($dimension['value']);
                    break;
                case TicketDimensionEnum::DIMENSION_EXTERNAL_AMOUNT:
                    $externalTicketInfo->externalAmount = $dimension['value'];
                    break;
                case TicketDimensionEnum::DIMENSION_SOURCE_WALLET:
                    $externalTicketInfo->sourceWallet = $dimension['value'];
                    break;
                case TicketDimensionEnum::DIMENSION_PROTECTION_CODE:
                    $externalTicketInfo->protectionCode = $dimension['value'];
                    break;
                case TicketDimensionEnum::DIMENSION_ADVERTISING_COMPANY_ID:
                    $externalTicketInfo->advertisingCompanyId = $dimension['value'];
                    break;
            }
        }

        return $externalTicketInfo;
    }
}
