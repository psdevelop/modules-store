<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 12.04.17
 * Time: 10:48
 */

namespace app\models\cabinet;

use app\components\enums\ContactTypesEnum;
use app\components\helpers\DbHelper;
use app\components\helpers\TimerHelper;
use app\models\sync\SyncBase;

/**
 * Class CabinetAccountNote
 * @property integer $id
 * @property string $synchronized
 * @property string $type
 * @property integer $account_id
 * @property string $created
 * @property string $note
 * @property int $employee_id
 * @package app\models\cabinet
 */
class CabinetAccountNote extends CabinetBase
{

    public static $table = 'account_notes';
    public static $createdField = 'created';
    public $planfixId;

    public static function getAllJoinedNotes($dateFrom, $dateTo)
    {
        self::setDb('dbLeads');
        $connection = self::getDb();

        $table = self::$table;
        $leadsDb = DbHelper::getDbName(\Yii::$app->dbLeads);
        $tradeDb = DbHelper::getDbName(\Yii::$app->dbTradeLeads);
        $syncDb = DbHelper::getDbName(\Yii::$app->dbPlanfixSync);

        $leadsTable = $leadsDb . '.' . $table;
        $tradeTable = $tradeDb . '.' . $table;
        $syncTable = $syncDb . '.' . SyncBase::$syncNotesTable;

        TimerHelper::timerRun();
        $command = $connection->createCommand(
            "
            SELECT
                $syncTable.id as sync_id,
                $leadsTable.id as leads_id,
                null as trade_id,
                $leadsTable.note as note,
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
        TimerHelper::timerStop(null, "Fetch Leads notes", "DB");

        TimerHelper::timerRun();
        $command = $connection->createCommand(
            "
            SELECT
                $syncTable.id as sync_id,
                null as leads_id,
                $tradeTable.id as trade_id,
                $tradeTable.note as note,
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
        TimerHelper::timerStop(null, "Fetch TradeLeads notes", "DB");
        return array_merge($firstChunk, $secondChunk);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        $this->setDbById();
        return $this->hasOne(CabinetEmployee::className(), ['id' => 'employee_id']);
    }

    public function getClient()
    {
        $this->setDbById();
        $type = $this->type;

        if ($type != ContactTypesEnum::TYPE_AFFILIATE && $type != ContactTypesEnum::TYPE_ADVERTISER) {
            return null;
        }

        $relativeClass = str_replace('Base', ucfirst($type), CabinetBase::class);
        return $this->hasOne($relativeClass, ['id' => 'account_id']);
    }
}
