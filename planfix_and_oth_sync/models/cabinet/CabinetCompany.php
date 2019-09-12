<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 17.04.17
 * Time: 8:02
 */

namespace app\models\cabinet;

use app\components\enums\ContactTypesEnum;
use app\components\helpers\DbHelper;
use app\components\helpers\TimerHelper;
use app\models\sync\SyncBase;

/**
 * Class CabinetCompany
 * @method getAccountNotes()
 * @method getChats()
 * @property $employee
 *
 * @property string $company
 *
 * @package app\models\cabinet
 */
class CabinetCompany extends CabinetBase
{
    public static $modifiedField = 'modified';
    public static $createdField = 'date_added';

    public static $allowedTypes = [
        ContactTypesEnum::TYPE_ADVERTISER,
        ContactTypesEnum::TYPE_AFFILIATE
    ];

    /**
     * @param $dateFrom
     * @param $dateTo
     * @return array|bool
     */
    public static function getAllJoinedAdvertisers($dateFrom, $dateTo)
    {
        return self::getAllJoined(ContactTypesEnum::TYPE_ADVERTISER, $dateFrom, $dateTo);
    }

    /**
     * Слитые воедино объекты Leads / TradeLeads
     * @param $type
     * @param $dateFrom
     * @param $dateTo
     * @return array|bool
     */
    public static function getAllJoined($type, $dateFrom, $dateTo)
    {
        if (!$type) {
            return false;
        }

        if (!in_array($type, self::$allowedTypes)) {
            return false;
        }

        self::setDb('dbPlanfixSync');
        $connection = self::getDb();


        $table = $type . 's';
        $leadsDb = DbHelper::getDbName(\Yii::$app->dbLeads);
        $tradeDb = DbHelper::getDbName(\Yii::$app->dbTradeLeads);
        $syncDb = DbHelper::getDbName(\Yii::$app->dbPlanfixSync);

        $leadsTable = $leadsDb . '.' . $table;
        $tradeTable = $tradeDb . '.' . $table;
        $syncTable = $syncDb . '.' . SyncBase::$syncCompaniesTable;

        TimerHelper::timerRun();
        $command = $connection->createCommand(
            "               
                SELECT
                    $syncTable.id as sync_id,
                    '$type' as type,
                    $leadsTable.id as leads_id,
                    NULL as trade_id,
                    $syncTable.leads_id as sync_leads_id,
                    $syncTable.trade_id as sync_trade_id
                FROM 
                    $leadsTable 
                LEFT JOIN $syncTable
                    ON 
                      $leadsTable.id = $syncTable.leads_id AND $syncTable.type = '$type'
                WHERE
                    $leadsTable.modified BETWEEN '$dateFrom' AND  '$dateTo'
        "
        );
        $firstChunk = $command->queryAll();

        TimerHelper::timerStop(null,"Fetch Leads $type","DB");

        TimerHelper::timerRun();
        $command = $connection->createCommand(
            "
                SELECT
                    $syncTable.id as sync_id,
                    '$type' as type,
                    NULL as leads_id,
                    $tradeTable.id as trade_id,
                    $syncTable.leads_id as sync_leads_id,
                    $syncTable.trade_id as sync_trade_id
                FROM 
                    $tradeTable 
                LEFT JOIN $syncTable
                    ON 
                      $tradeTable.id = $syncTable.trade_id AND $syncTable.type = '$type'
                WHERE
                    $tradeTable.modified BETWEEN '$dateFrom' AND  '$dateTo'
        "
        );

        $secondChunk = $command->queryAll();
        TimerHelper::timerStop(null,"Fetch TradeLeads $type","DB");

        return array_merge($firstChunk, $secondChunk);
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @return array|bool
     */
    public static function getAllJoinedAffiliates($dateFrom, $dateTo)
    {
        return self::getAllJoined(ContactTypesEnum::TYPE_AFFILIATE, $dateFrom, $dateTo);
    }

    public function getCreatedChats($dateFrom, $dateTo)
    {
        return $this->getChats()
            ->where(['>', 'created', $dateFrom])
            ->andWhere(['>', 'created', $dateTo])
            ->all();
    }

    /**
     * Пользователи объекта модели
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        $this->setDbById();
        return $this->hasOne(CabinetEmployee::className(), ['id' => 'employee_id']);
    }

    public function getFullName()
    {
        return $this->company;
    }

    public function getFullPlanfixName()
    {
        return sprintf('%s, %s', $this->getBasePrefix(), $this->getFullName());
    }

}
