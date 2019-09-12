<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 26.05.17
 * Time: 17:15
 */

namespace app\models\cabinet;

use app\components\helpers\DbHelper;
use app\models\sync\SyncBase;

/**
 * Class CabinetAffiliateBillingFastPayoutRequest
 * @property CabinetAffiliateBillingPayoutRequest $commission
 * @property CabinetAffiliate $affiliate
 * @package app\models\cabinet
 */
class CabinetAffiliateBillingFastPayoutRequest extends CabinetAffiliateBillingPayoutRequest
{

    /**
     * Мердж Leads/TradeLeads запросов на срочные выплаты
     * @return array
     */
    public static function getAllNeedApprove()
    {
        self::setDb('dbPlanfixSync');
        $connection = self::getDb();

        $table = self::$table;
        $leadsDb = DbHelper::getDbName(\Yii::$app->dbLeads);
        $tradeDb = DbHelper::getDbName(\Yii::$app->dbTradeLeads);
        $syncDb = DbHelper::getDbName(\Yii::$app->dbPlanfixSync);

        $leadsTable = $leadsDb . '.' . $table;
        $tradeTable = $tradeDb . '.' . $table;
        $syncTable = $syncDb . '.' . SyncBase::$syncPaymentsTable;
        $neededStatus = 'need_approve';

        $command = $connection->createCommand(
            "               
                SELECT
                    t1.id,
                    tt1.id as comission_request_id,
                    tt1.amount as commission_sum,
                    s.id as sync_id,
                    t1.id as leads_id,
                    null as trade_id,
                    t1.amount,
                    t1.status
                FROM
                    $leadsTable AS t1
                LEFT JOIN $syncTable AS s
                    ON t1.id  = s.leads_id
                LEFT JOIN $leadsTable as tt1
                    ON tt1.base_request_id = t1.id
                WHERE
                    t1.fast_request_type = 'request' 
                      AND
                    t1.status = '$neededStatus'
                      AND
                    s.id is null
        "
        );

        $firstChunk = $command->queryAll();

        $command = $connection->createCommand(
            "
                SELECT
                    t1.id,
                    tt1.id as comission_request_id,
                    tt1.amount as commission_sum,
                    s.id as sync_id,
                    null as lead_id,
                    t1.id as trade_id,
                    t1.amount,
                    t1.status
                FROM
                    $tradeTable AS t1
                LEFT JOIN $syncTable AS s
                    ON t1.id  = s.trade_id
                LEFT JOIN $tradeTable as tt1
                    ON tt1.base_request_id = t1.id
                WHERE
                    t1.fast_request_type = 'request' 
                      AND
                    t1.status = '$neededStatus'
                      AND
                    s.id is null
        "
        );
        $secondChunk = $command->queryAll();
        return array_merge($firstChunk, $secondChunk);
    }

    /**
     * Объект связанной комиссии
     * @return \yii\db\ActiveQuery
     */
    public function getCommission()
    {
        $this->setDbById();
        return $this->hasOne(self::className(), ['base_request_id' => 'id']);
    }

    public function hasFinalStatus()
    {
        return $this->status == self::STATUS_PAYMENT_REJECTED || $this->status == self::STATUS_PAYMENT_APPROVED;
    }

    public function isPaid()
    {
        return $this->status == self::STATUS_PAYMENT_PAID;
    }
}