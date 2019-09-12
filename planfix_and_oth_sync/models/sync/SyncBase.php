<?php

namespace app\models\sync;

use app\components\helpers\LogHelper;
use app\components\helpers\TimerHelper;
use app\models\cabinet\CabinetBase;
use yii\db\ActiveRecord;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 18.04.17
 * Time: 15:18
 *
 * @property integer $planfix_id
 * @property integer $planfix_userid
 * @property $leadsObject
 * @property $tradeObject
 * @property $leads_id
 * @property $trade_id
 * @property $status_sync
 * @property $type
 * @property $leads_cid
 * @property $trade_cid
 * @property $planfix_general_id
 * @property $planfix_task_id
 *
 */
class SyncBase extends ActiveRecord
{
    const STATUS_ADD = 'add';
    const STATUS_UPDATE = 'update';
    const STATUS_NONE = 'none';

    public static $table;

    public static $syncUsersTable = 'planfix_users_sync';
    public static $syncCompaniesTable = 'planfix_company_sync';
    public static $syncNotesTable = 'planfix_notes_sync';
    public static $syncPaymentsTable = 'planfix_payments_requests';
    public static $syncChatsTable = 'planfix_chats_sync';
    public static $syncOffersTable = 'planfix_offers_sync';
    public static $syncTicketsTable = 'planfix_tickets_sync';

    public static function getDb()
    {
        return \Yii::$app->dbPlanfixSync;
    }

    /**
     * @param $object CabinetBase
     * @param null $base
     * @return SyncBase|ActiveRecord
     */
    public static function getByCabinet($object, $base = null)
    {
        return $base
            ? static::find()
                ->where("{$base}_id = $object->id")
                ->one()
            : static::find()
                ->where("leads_id = $object->id")
                ->orWhere("trade_id = $object->id")
                ->one();
    }

    /**
     * tableName
     * @return string
     */
    public static function tableName()
    {
        return static::$table;
    }

    /**
     * @return array|ActiveRecord[]
     */
    public static function toAdd()
    {
        return static::find()->where(['=', 'status_sync', 'add'])->all();
    }

    /**
     * @return array|ActiveRecord[]
     */
    public static function toUpdate()
    {
        return static::find()->where(['=', 'status_sync', 'update'])->all();
    }

    /**
     * Потребовать обновить все записи из таблиц синхронизации
     * @return bool
     */
    public static function forceUpdate()
    {
        if (!static::$table) {
            LogHelper::critical("Table is not set.");
            return false;
        }
        LogHelper::action("FORCE UPDATE " . static::$table);
        $syncObjects = static::find()->where(['=', 'status_sync', SyncBase::STATUS_NONE])->all();
        foreach ($syncObjects as $syncObject) {
            /**
             * @var $syncObject SyncPlanfixOffers
             */
            $syncObject->status_sync = SyncBase::STATUS_UPDATE;
            $syncObject->save();
        }
        LogHelper::success("Updated: " . count($syncObjects));
        return true;
    }

    public function getLeadsObject()
    {
        return $this->getCabinetObject('leads');
    }

    protected function getCabinetObject($base)
    {
        $base = strtolower($base);
        CabinetBase::setDb('db' . ($base == 'trade' ? 'Trade' : '') . 'Leads');
    }

    public function getTradeObject()
    {
        return $this->getCabinetObject('trade');
    }

    /**
     * @return CabinetBase
     */
    public function getSyncCabinetObject()
    {
        TimerHelper::timerRun();
        if ($leadsObject = $this->leadsObject) {
            $leadsObject->leads_id = $this->leads_id;
            $leadsObject->trade_id = $this->trade_id;
            $leadsObject->base = 'leads';
            $object = $leadsObject;
        } elseif ($tradeObject = $this->tradeObject) {
            $tradeObject->leads_id = $this->leads_id;
            $tradeObject->trade_id = $this->trade_id;
            $tradeObject->base = 'trade';
            $object = $tradeObject;
        } else {
            TimerHelper::timerStop(null, "get sync Cabinet Object");
            return null;
        }
        TimerHelper::timerStop(null, "get sync Cabinet Object");
        return $object;
    }

}
