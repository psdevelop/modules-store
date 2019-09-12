<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 20.04.17
 * Time: 14:14
 */

namespace app\models\sync;

use app\components\helpers\LogHelper;
use app\models\cabinet\CabinetAffiliateBillingFastPayoutRequest;

/**
 * Class SyncPlanfixPayments
 *
 * @property int $id
 * @property string $status_sync
 * @property int $leads_id
 * @property int $trade_id
 * @property int $planfix_id
 * @property float $amount
 * @property string $status_payment
 * @property float $sum
 * @property float $sum_commission
 * @package app\models\sync
 */
class SyncPlanfixPayments extends SyncBase
{
    const STATUS_PAYMENT_NEW = "new";
    const STATUS_PAYMENT_NEED_APPROVE = "need_approve";
    const STATUS_PAYMENT_PENDING = "pending";
    const STATUS_PAYMENT_REJECTED = "rejected";
    const STATUS_PAYMENT_PAID = "paid";

    public static $table = 'planfix_payments_requests';

    /**
     * Обновление таблицы синхронизации по кабинетвм
     * @param $objects
     */
    public static function updateSync($objects)
    {
        LogHelper::action("Prepare " . static::$table . " table for sync...");

        foreach ($objects as $object) {
            if ($object['sync_id']) {
                continue;
            }
            $sync = new static();
            $sync->status_sync = self::STATUS_ADD;
            $sync->leads_id = $object['leads_id'] ?? null;
            $sync->trade_id = $object['trade_id'] ?? null;
            $sync->sum_commission = (float)$object['commission_sum'] ?? null;
            $sync->sum = (float)$object['amount'] ?? null;
            $sync->status_payment = self::STATUS_PAYMENT_NEW;
            $sync->save();
        }
    }

    /**
     * Получить объект кабинета
     * @param $base
     * @return \yii\db\ActiveQuery
     */
    public function getCabinetObject($base)
    {
        $base = strtolower($base);
        CabinetAffiliateBillingFastPayoutRequest::setDb('db' . ($base == 'trade' ? 'Trade' : '') . 'Leads');
        return $this->hasOne(CabinetAffiliateBillingFastPayoutRequest::class, ['id' => $base . '_id']);
    }

    /**
     * Все запросы, требующие согласования
     * @return self[]
     */
    public static function needApprove()
    {
        return self::find()
            ->where(['=', 'status_payment', 'need_approve'])
            ->all();
    }
}
