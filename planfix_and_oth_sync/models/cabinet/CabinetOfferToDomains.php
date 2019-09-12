<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 07.09.17
 * Time: 18:06
 */

namespace app\models\cabinet;

use app\models\planfix\PlanfixOfferTask;

/**
 * Class CabinetOfferToDomains
 * @property int $id
 * @property CabinetOffer $offer
 * @property CabinetTrackingDomain $domain
 * @property string $status
 * @property $last_comment
 * @package app\models\cabinet
 */
class CabinetOfferToDomains extends CabinetBase
{
    const DOMAIN_STATUS_NONE = 'none';
    const DOMAIN_STATUS_INSTALLED = 'installed';
    const DOMAIN_STATUS_INSTALLING = 'installing';
    const DOMAIN_STATUS_TESTING = 'testing';
    const DOMAIN_STATUS_NOT_REQUIRED = 'not_required';

    public static $table = 'offers_to_tracking_domains';

    public function getPlanfixIdStatus()
    {
        $map = PlanfixOfferTask::instance()->getIdStatusesToCabinetMap();
        return array_search($this->status, $map) ?? null;
    }

    public function getOffer()
    {
        $this->setDbById();
        return $this
            ->hasOne(CabinetOffer::className(), ['id' => 'offer_id']);
    }

    public function getDomain()
    {
        $this->setDbById();
        return $this
            ->hasOne(CabinetTrackingDomain::className(), ['id' => 'tracking_domain_id']);
    }

    /**
     * @param $offerId
     * @param $domainName
     * @return self|null|\yii\db\ActiveRecord
     */
    public static function getByOfferDomain($offerId, $domainName, $base)
    {
        self::setDbByBase($base);
        $offerToDomain = self::find()
            ->where(['=', 'domain', $domainName])
            ->andWhere(['=', 'offer_id', $offerId])
            ->joinWith('domain')
            ->one();
        return $offerToDomain;
    }
}