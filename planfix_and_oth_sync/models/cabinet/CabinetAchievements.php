<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 07.09.17
 * Time: 18:06
 */

namespace app\models\cabinet;

use app\components\enums\ContactTypesEnum;
use app\models\planfix\PlanfixOfferTask;

/**
 * Class CabinetAchievements
 * @property int $id
 * @property string $achieve_code
 * @property string $account_type
 * @property int $account_id
 * @property $created_at
 * @property $handled_at
 * @property $closed_at
 *
 * @property CabinetAdvertiser $advertiser
 * @property CabinetAffiliate $affiliate
 * @property CabinetEmployee $manager
 * @property CabinetAffiliate | CabinetAdvertiser | CabinetEmployee $client
 *
 * @package app\models\cabinet
 */
class CabinetAchievements extends CabinetBase
{
    public static $table = 'achievements';


    public function getPlanfixIdStatus()
    {
        $map = PlanfixOfferTask::instance()->getIdStatusesToCabinetMap();
        return array_search($this->status, $map) ?? null;
    }

    public function getAffiliate()
    {
        $this->setDbById();
        return $this->hasOne(CabinetAffiliate::className(), ['id' => 'account_id']);
    }

    public function getAdvertiser()
    {
        $this->setDbById();
        return $this->hasOne(CabinetAdvertiser::className(), ['id' => 'account_id']);
    }

    public function getManager()
    {
        $this->setDbById();
        return $this->hasOne(CabinetEmployee::className(), ['id' => 'account_id']);
    }

    public function getClient()
    {
        $method = 'get'.ucfirst($this->account_type);
        if(!method_exists($this,$method)){
            return null;
        }
        return $this->$method();
    }
}
