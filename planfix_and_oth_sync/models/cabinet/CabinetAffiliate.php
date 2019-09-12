<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 29.03.2017
 */

namespace app\models\cabinet;

use app\components\enums\ContactTypesEnum;
use app\models\planfix\PlanfixBase;
use app\models\sync\SyncPlanfixCompanies;

/**
 * Class CabinetAffiliate
 * @property integer $id
 * @property string $synchronized
 * @property integer $employee_id
 * @property string $company
 * @property string $display_name
 * @property string $address1
 * @property string $address2
 * @property integer $city_id
 * @property integer $region_id
 * @property integer $country_id
 * @property string $other
 * @property string $zipcode
 * @property string $phone
 * @property string $fax
 * @property string $website
 * @property string $signup_ip
 * @property string $date_added
 * @property string $status
 * @property string $payment_method
 * @property string $method_data
 * @property integer $w9_filed
 * @property integer $referral_id
 * @property string $affiliate_tier_id
 * @property string $ref_id
 * @property string $modified
 * @property integer $is_active
 * @property integer $is_system
 * @property integer $affiliate_group_id
 * @property integer $payment_type_id
 * @property string $payment_type_data_to_drop
 * @property string $preferable_contact
 * @property string $skype
 * @property string $icq
 * @property string $cell_phone
 * @property string $legal_type
 * @property string $legal_type_name
 * @property string $confirm_cell_phone
 * @property string $confirm_payment_data
 * @property string $payment_period
 * @property string $backurl_default
 * @property string $backurl_geo
 * @property string $backurl_browser
 * @property string $backurl_conversions_total
 * @property float $balance
 * @property float $points
 * @property float $hold_in
 * @property float $hold_out
 * @property string $privileges_level
 * @property string $referer
 * @property string $source
 * @property float $referral_rate
 * @property int $test_mode
 * @property int $hold_days
 * @property float $disable_payout
 * @property integer $is_distrust
 * @property string $distrust_hint
 * @property string $birthday
 * @property string $payment_employee_note
 * @property float $credit_limit
 * @property integer $is_available_tor
 *
 * @property SyncPlanfixCompanies $planfix
 *
 * @package app\models
 */
class CabinetAffiliate extends CabinetCompany
{
    public static $table = 'affiliates';

    public function getAccountNotes()
    {
        $this->setDbById();
        return $this->hasMany(CabinetAccountNote::className(), ['account_id' => 'id'])
            ->where(['=', CabinetAccountNote::$table . '.type', ContactTypesEnum::TYPE_AFFILIATE]);
    }

    public function getChats()
    {
        $this->setDbById();
        return $this->hasMany(CabinetChat::className(), ['account_id' => 'id'])
            ->where(['=', CabinetChat::$table . '.account_type', ContactTypesEnum::TYPE_AFFILIATE]);
    }

    public function getPlanfix()
    {
        $this->setDbById();
        return SyncPlanfixCompanies::find()
            ->where(['=', 'type', ContactTypesEnum::TYPE_AFFILIATE])
            ->andWhere(['=', 'leads_id', $this->leads_id])
            ->orWhere(['=', 'trade_id', $this->trade_id])
            ->one();
    }

    /**
     * @param $cabinet
     * @return mixed
     */
    public function getCabinetUrl($cabinet = 'manager')
    {
        $cabinets = PlanfixBase::instance()->cabinets;
        return sprintf("http://%s/webmasters/default/view/%d", $cabinets[$this->base][$cabinet], $this->id);
    }

    /**
     * @param $grouping
     * @param $dateFrom
     * @param $dateTo
     * @param string $cabinet
     * @return null|string
     */
    public function getCabinetSummaryUrl($grouping, $dateFrom, $dateTo, $cabinet = 'manager')
    {
        $args = http_build_query([
            'grouping' => $grouping,
            'dateRange' => "$dateFrom-$dateTo",
            'affiliate' => $this->id,
        ]);

        $cabinets = PlanfixBase::instance()->cabinets;
        $cabinet = $cabinets[$this->base][$cabinet] ?? null;
        if(!$cabinet){
            return null;
        }

        return sprintf("http://%s/reports/summary?%s", $cabinet, $args);
    }
}
