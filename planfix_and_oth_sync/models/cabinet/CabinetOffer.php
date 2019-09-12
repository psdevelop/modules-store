<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 05.09.17
 * Time: 10:59
 */

namespace app\models\cabinet;

use app\components\helpers\DbHelper;
use app\components\helpers\TimerHelper;
use app\models\planfix\PlanfixBase;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixOffers;

/**
 * Class CabinetOffer
 * @property integer $id
 * @property string $synchronized
 * @property integer $advertiser_id
 * @property string $name
 * @property string $description
 * @property string $offer_url
 * @property string $preview_url
 * @property string $payout
 * @property string $payout_percent
 * @property integer $point_percents
 * @property string $revenue
 * @property string $expiration_date
 * @property integer $deeplink_enabled
 * @property integer $in_carousel
 * @property string $home_page_visible
 * @property string $created
 * @property string $protocol
 * @property string $status
 * @property integer $is_private
 * @property integer $has_limits
 * @property integer $hold_days
 * @property integer $redirect_offer_id
 * @property integer $session_hours
 * @property string $payout_type
 * @property string $revenue_type
 * @property integer $approve_conversions
 * @property integer $enforce_geo_targeting
 * @property integer $enforce_browser_targeting
 * @property string $featured
 * @property integer $ref_id
 * @property integer $is_end_point
 * @property string $modified
 * @property integer $has_goals_enabled
 * @property string $default_goal_name
 * @property string $note
 * @property string $logo
 * @property string $require_platform_approval
 * @property string $stat_update_last
 * @property string $stat_update_next
 * @property string $stat_update_type
 * @property string $icon
 * @property string $auto_status_conversion
 * Site fields
 * @property integer $site_visible
 * @property integer $site_sort_order
 * @property string $site_name
 * @property string $site_condition
 * @property string $site_description
 * @property string $site_benefits
 * @property string $site_available_date
 * @property integer $site_best_deals
 * @property integer $verify_claims
 * @property integer $average_verify_claims
 * @property integer $feed_visible
 * @property integer $feed_recommended
 * Rating
 * @property integer $rating_common             Общий рейтинг с учетом всех факторов
 * @property integer $rating_common_speed       Рейтинг с учетом головосания Скорость обслуживания
 * @property integer $rating_common_quality     Рейтинг с учетом головосания Качество обслуживания
 * @property integer $rating_common_popular     Рейтинг с учетом головосания Популярность
 * @property integer $rating_common_convenience Рейтинг с учетом головосания Демократичность (количество бумаг)
 * @property integer $rating_speed              Рейтинг выставленый в ЛК Скорость обслуживания
 * @property integer $rating_quality            Рейтинг выставленый в ЛК Качество обслуживания
 * @property integer $rating_popular            Рейтинг выставленый в ЛК Популярность
 * @property integer $rating_convenience        Рейтинг выставленый в ЛК Демократичность (количество бумаг)
 * @property string $valuta_id
 * @property string $valuta_payout
 * @property string $valuta_revenue
 * @property integer $external_id ID Оффера в партнерской системе
 * @property integer $goal_external_id ID Цели в партнерской системе
 * @property integer $employee_id ID Менеджер отвечающий за оффер
 * @property string $external_deeplink_param_name Параметр партнерской сети для диплинка
 * @property string $role роль оффера(фаворит или сателлит)
 * @property integer $is_scale_of_charges Тарифная сетка
 * @property string $check_text      Фразы для определения доступности страницы
 * @property string $check_no_text      Фразы для определения недоступности страницы
 * @property string $on_if_enabled      Включать оффер при доступности страницы
 * @property string $off_if_not_enabled      Останавливать оффер при недоступности страницы
 * @property string $is_notify_manager      Уведомлять менеджера оффера при недоступности страницы
 * @property string $last_checked    Дата проверки доступности посадочной страницы
 * @property string $response_status_code    Код статуса ответа посадочной страницы
 * @property string $response_status_message Сообщение статуса ответа посадочной страницы
 * @property string $scale_of_charges_info Информация о тарифном плане
 * @property string $multipush_priority Приоритет отправки анкеты оффера залитой по мультипушу
 * @property float $epl_default Среднее значение EPL если нет аналитики за период
 * @property integer $epl_days Количество дней для расчета среднего EPL
 * @property integer $epl_offer_conversions
 * @property integer $epl_affiliate_conversions
 * @property integer $multipush_pause
 * @property string $revenue_fix
 * @property string $revenue_percent
 * @property string $revenue_delta
 * @property string $revenue_valuta_id
 * @property string $period_conversion_approved
 * @property string $stub_priority
 *
 * @property CabinetEmployee $employee
 * @property CabinetAdvertiser $advertiser
 * @package app\models\cabinet
 */
class CabinetOffer extends CabinetBase
{
    public static $createdField = 'created';
    public static $table = 'offers';

    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_PENDING = 'pending';
    const STATUS_DELETED = 'deleted';

    public static $ruStatuses = [
        self::STATUS_ACTIVE => 'Активный',
        self::STATUS_PENDING => 'Ожидает подтверждения',
        self::STATUS_PAUSED => 'На паузе',
        self::STATUS_DELETED => 'Удалён',
    ];

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        $this->setDbById();
        return $this->hasOne(CabinetEmployee::className(), ['id' => 'employee_id']);
    }

    public function getPlanfix()
    {
        $this->setDbById();
        return SyncPlanfixOffers::find()
            ->andWhere(['=', 'leads_id', $this->leads_id])
            ->orWhere(['=', 'trade_id', $this->trade_id])
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdvertiser()
    {
        $this->setDbById();
        return $this->hasOne(CabinetAdvertiser::className(), ['id' => 'advertiser_id']);
    }

    public static function getAllJoinedOffers($dateFrom, $dateTo)
    {
        self::setDb('dbLeads');
        $connection = self::getDb();

        $table = self::$table;
        $leadsDb = DbHelper::getDbName(\Yii::$app->dbLeads);
        $tradeDb = DbHelper::getDbName(\Yii::$app->dbTradeLeads);
        $syncDb = DbHelper::getDbName(\Yii::$app->dbPlanfixSync);

        $leadsTable = $leadsDb . '.' . $table;
        $tradeTable = $tradeDb . '.' . $table;
        $syncTable = $syncDb . '.' . SyncBase::$syncOffersTable;

        TimerHelper::timerRun();
        $command = $connection->createCommand(
            "
            SELECT
                'leads' as base,
                $syncTable.id as sync_id,
                $leadsTable.id as leads_id,
                null as trade_id,
                $syncTable.leads_id as sync_leads_id,
                null as sync_trade_id
            FROM 
                $leadsTable
            LEFT JOIN $syncTable
                ON $leadsTable.id = $syncTable.leads_id
            WHERE
                $leadsTable.modified BETWEEN '$dateFrom' AND  '$dateTo'
            ;"
        );
        $firstChunk = $command->queryAll();

        TimerHelper::timerStop(null, "Fetch Leads Offers. Total: " . count($firstChunk), "DB");
        TimerHelper::timerRun();
        $command = $connection->createCommand(
            "
            SELECT
                'trade' as base,
                $syncTable.id as sync_id,
                null as leads_id,
                $tradeTable.id as trade_id,
                null as sync_leads_id,
                $syncTable.trade_id as sync_trade_id
            FROM 
                $tradeTable
            LEFT JOIN $syncTable
                ON $tradeTable.id = $syncTable.trade_id
            WHERE
                $tradeTable.modified BETWEEN '$dateFrom' AND  '$dateTo'
            ;"
        );
        $secondChunk = $command->queryAll();
        TimerHelper::timerStop(null, "Fetch TradeLeads Offers. Total: " . count($secondChunk), "DB");
        return array_merge($firstChunk, $secondChunk);
    }

    /**
     * Ru статус
     * @return mixed|null
     */
    public function getRuStatus()
    {
        return self::$ruStatuses[$this->status] ?? null;
    }

    /**
     * @param $cabinet
     * @return mixed
     */
    public function getCabinetUrl($cabinet = 'manager')
    {
        $cabinets = PlanfixBase::instance()->cabinets;
        return sprintf("http://%s/offers/default/view/%d", $cabinets[$this->base][$cabinet] ?? null, $this->id);
    }
}
