<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 29.03.2017
 */

namespace app\models\cabinet;

use app\components\enums\ContactTypesEnum;
use app\models\planfix\PlanfixBase;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixCompanies;
/**
 * Class CabinetAdvertiser
 * @property integer $id
 * @property string $synchronized
 * @property string $date_added
 * @property string $status
 * @property string $company
 * @property string $address1
 * @property string $address2
 * @property string $other
 * @property string $zipcode
 * @property string $phone
 * @property string $fax
 * @property string $website
 * @property integer $employee_id
 * @property string $signup_ip
 * @property string $modified
 * @property integer $ref_id
 * @property string $cell_phone
 * @property int $confirm_cell_phone
 * @property int $confirm_payment_data
 * @property string $icq
 * @property string $skype
 * @property string $preferable_contact
 * @property integer $advertiser_group_id
 * @property integer $city_id
 * @property integer $region_id
 * @property integer $country_id
 * @property integer $api_enabled
 * @property string $api_login
 * @property string $api_password
 * @property string $api_key
 * @property string $api_url
 * @property string $api_assoc
 * @property string $referrer
 * @property string $source
 *
 * @package app\models
 */
class CabinetAdvertiser extends CabinetCompany
{
    public static $table = 'advertisers';

    public function getAccountNotes()
    {
        $this->setDbById();
        return $this->hasMany(CabinetAccountNote::className(), ['account_id' => 'id'])
            ->where(['=', 'account_notes.type', ContactTypesEnum::TYPE_ADVERTISER]);
    }

    public function getChats()
    {
        $this->setDbById();
        return $this->hasMany(CabinetChat::className(), ['account_id' => 'id'])
            ->where(['=', CabinetChat::$table . '.account_type', ContactTypesEnum::TYPE_ADVERTISER]);
    }

    /**
     * @return SyncBase|\yii\db\ActiveRecord
     */
    public function getPlanfix()
    {
        $this->setDbById();
        return SyncPlanfixCompanies::find()
            ->where(['=', 'type', ContactTypesEnum::TYPE_ADVERTISER])
            ->andWhere(['=', 'leads_id', $this->leads_id])
            ->orWhere(['=', 'trade_id', $this->trade_id])
            ->one();
    }


    /**
     * @param $object CabinetAdvertiser
     * @param $value
     * @return mixed
     */
    public function getCabinetAdvertiserUrl($object, $value)
    {
        return sprintf("http://%s/advertizers/default/view/%d", $this->cabinets[$object->base][$value],$object->id);
    }

    /**
     * @param $cabinet
     * @return mixed
     */
    public function getCabinetUrl($cabinet = 'manager')
    {
        $cabinets = PlanfixBase::instance()->cabinets;
        return sprintf("http://%s/advertizers/default/view/%d", $cabinets[$this->base][$cabinet],$this->id);
    }
}
