<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 21.06.17
 * Time: 15:44
 */

namespace app\models\cabinet;

use app\models\planfix\PlanfixPayoutTask;
use app\models\planfix\PlanfixTask;

/**
 * This is the model class for table "affiliate_billing_payout_requests".
 * @property integer $id
 * @property string $created
 * @property string $expected
 * @property integer $affiliate_id
 * @property string $affiliate_comment
 * @property float $amount
 * @property string $paid
 * @property string $status
 * @property string $legal_type
 * @property string $type
 * @property string $fast_request_type
 * @property integer $payment_type_id
 * @property string $payment_type_data
 * @property integer $employee_id
 * @property string $employee_comment
 * @property string $employee_comment_modified
 * @property string $employee_note
 */
class CabinetAffiliateBillingPayoutRequest extends CabinetBase
{
    const STATUS_PAYMENT_REJECTED = 'rejected';
    const STATUS_PAYMENT_APPROVED = 'pending';
    const STATUS_PAYMENT_NEED_APPROVE = 'need_approve';
    const STATUS_PAYMENT_PAID = 'paid';

    public static $statusesPlanfixCabinet = [
        PlanfixPayoutTask::TASK_STATUS_NEW => self::STATUS_PAYMENT_NEED_APPROVE,
        PlanfixPayoutTask::TASK_STATUS_WORK => self::STATUS_PAYMENT_NEED_APPROVE,
        PlanfixPayoutTask::TASK_STATUS_REJECTED => self::STATUS_PAYMENT_REJECTED,
        PlanfixPayoutTask::TASK_STATUS_CANCELED => self::STATUS_PAYMENT_REJECTED,
        PlanfixPayoutTask::TASK_STATUS_DONE => self::STATUS_PAYMENT_APPROVED,
    ];

    public static $statusesCabinetPlanfix = [
        self::STATUS_PAYMENT_REJECTED => PlanfixTask::TASK_STATUS_REJECTED,
        self::STATUS_PAYMENT_APPROVED => PlanfixTask::TASK_STATUS_DONE,
        self::STATUS_PAYMENT_PAID => PlanfixTask::TASK_STATUS_COMPLETED,
    ];

    public static $statusesCabinetRu = [
        self::STATUS_PAYMENT_NEED_APPROVE => 'Ожидает согласование',
        self::STATUS_PAYMENT_REJECTED => 'Отклонен',
        self::STATUS_PAYMENT_APPROVED => 'Ожидает оплату',
        self::STATUS_PAYMENT_PAID => 'Оплачен'
    ];

    public static function getClientStatus(int $statusId)
    {
        return self::$statusesPlanfixCabinet[$statusId] ?? null;
    }

    public static function getRuStatus($status)
    {
        if (is_numeric($status)) {
            return self::$statusesCabinetRu[self::getClientStatus($status)] ?? null;
        } elseif (is_string($status)) {
            return self::$statusesCabinetRu[$status] ?? null;
        }
        return null;
    }

    public static $table = 'affiliate_billing_payout_requests';

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAffiliate()
    {
        $this->setDbById();
        return $this->hasOne(CabinetAffiliate::className(), ['id' => 'affiliate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        $this->setDbById();
        return $this->hasOne(CabinetEmployee::className(), ['id' => 'employee_id']);
    }
}