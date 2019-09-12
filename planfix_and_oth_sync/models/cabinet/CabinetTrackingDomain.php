<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 07.09.17
 * Time: 14:25
 */

namespace app\models\cabinet;

/**
 * Class CabinetTrackingDomain
 *
 * @property int $id
 * @property string $domain Домен
 *
 * @package app\models\cabinet
 */
class CabinetTrackingDomain extends CabinetBase
{
    public static $table = 'tracking_domains';

    public function getOffers()
    {
        $this->setDbById();
        return $this
            ->hasMany(CabinetOffer::className(), ['id' => 'offer_id'])
            ->via('offersDomains');
    }

    public function getOffersDomains()
    {
        $this->setDbById();
        return $this
            ->hasMany(CabinetOfferToDomains::className(), ['id' => 'offer_id']);
    }
}