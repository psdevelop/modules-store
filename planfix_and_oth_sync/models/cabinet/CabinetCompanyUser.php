<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 17.04.17
 * Time: 8:02
 */

namespace app\models\cabinet;

use app\components\enums\ContactTypesEnum;
use app\components\helpers\TimerHelper;
use app\models\sync\SyncBase;
use PDO;
use app\components\helpers\DbHelper;

class CabinetCompanyUser extends CabinetBase
{
    public static $modifiedField = 'modified';
    public static $createdField = 'join_date';

    public static $allowedTypes = [
        ContactTypesEnum::TYPE_ADVERTISER,
        ContactTypesEnum::TYPE_AFFILIATE
    ];

    /**
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
        TimerHelper::timerRun();
        $table = $type . '_users';
        $typeSingular = $type;

        $leadsDb = DbHelper::getDbName(\Yii::$app->dbLeads);
        $tradeDb = DbHelper::getDbName(\Yii::$app->dbTradeLeads);
        $syncDb = DbHelper::getDbName(\Yii::$app->dbPlanfixSync);

        $leadsTable = $leadsDb . '.' . $table;
        $tradeTable = $tradeDb . '.' . $table;
        $syncTable = $syncDb . '.' . SyncBase::$syncUsersTable;

        $connection = \Yii::$app->getDb();
        $command = $connection->createCommand(
            "
                SELECT
                    $leadsTable.email,
                    $syncTable.id as sync_id,
                    '$type' as type,
                    $leadsTable.id as leads_id,
                    $tradeTable.id as trade_id,
                    $leadsTable.email as leads_email,
                    $tradeTable.email as trade_email,
                    $syncTable.leads_id as sync_leads_id,
                    $syncTable.trade_id as sync_trade_id,
                    $leadsTable.{$typeSingular}_id as leads_cid,
                    $tradeTable.{$typeSingular}_id as trade_cid
                FROM 
                    $leadsTable 
                LEFT JOIN $tradeTable 
                    ON $leadsTable.email = $tradeTable.email
                LEFT JOIN $syncTable
                    ON 
                      ($leadsTable.id = $syncTable.leads_id AND $syncTable.type = '$type')
                    OR 
                      ($tradeTable.id = $syncTable.trade_id AND $syncTable.type = '$type')
                WHERE
                    ($leadsTable.modified BETWEEN '$dateFrom' AND  '$dateTo'
                        OR
                    $tradeTable.modified BETWEEN '$dateFrom' AND  '$dateTo')
            ;"
        );

        $firstChunk = $command->queryAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        TimerHelper::timerStop(null,"Fetch Leads $type users","DB");
        TimerHelper::timerRun();
        $command = $connection->createCommand(
            "
                SELECT
                    $tradeTable.email,
                    $syncTable.id as sync_id,
                    '$type'  as type,
                    $leadsTable.id as leads_id,
                    $tradeTable.id as trade_id,
                    $leadsTable.email as leads_email,
                    $tradeTable.email as trade_email,
                    $syncTable.leads_id as sync_leads_id,
                    $syncTable.trade_id as sync_trade_id,
                    $leadsTable.{$typeSingular}_id as leads_cid,
                    $tradeTable.{$typeSingular}_id as trade_cid
                FROM 
                    $leadsTable 
                RIGHT JOIN $tradeTable 
                    ON $leadsTable.email = $tradeTable.email
                LEFT JOIN $syncTable
                    ON 
                      ($leadsTable.id = $syncTable.leads_id AND $syncTable.type = '$type')
                    OR 
                      ($tradeTable.id = $syncTable.trade_id AND $syncTable.type = '$type')
                WHERE
                    ($leadsTable.modified BETWEEN '$dateFrom' AND  '$dateTo'
                        OR
                    $tradeTable.modified BETWEEN '$dateFrom' AND  '$dateTo')     
            ;"
        );

        $secondChunk = $command->queryAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        TimerHelper::timerStop(null,"Fetch TradeLeads $type","DB");
        return $firstChunk + $secondChunk;
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @return array|bool
     */
    public static function getAllJoinedAdvertiserUsers($dateFrom, $dateTo)
    {
        return self::getAllJoined(ContactTypesEnum::TYPE_ADVERTISER, $dateFrom, $dateTo);
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @return array|bool
     */
    public static function getAllJoinedAffiliateUsers($dateFrom, $dateTo)
    {
        return self::getAllJoined(ContactTypesEnum::TYPE_AFFILIATE, $dateFrom, $dateTo);
    }

    public function getAdvertiser()
    {
        return $this->hasOne(CabinetAdvertiser::class, ['id' => 'advertiser_id']);
    }

    /**
     * Компания Вебмастер
     * @return \yii\db\ActiveQuery
     */
    public function getAffiliate()
    {
        return $this->hasOne(CabinetAffiliate::class, ['id' => 'affiliate_id']);
    }

    /**
     * Компания Рекламодатель или вбмастер
     * @return CabinetCompany
     */
    public function getCompany()
    {
        if($this instanceof CabinetAdvertiserUser) {
            return $this->advertiser;
        }

        if($this instanceof CabinetAffiliateUser) {
            return $this->affiliate;
        }
        return null;
    }

    /**
     * Ответственный контакта
     * @param $object CabinetCompany | CabinetCompanyUser
     * @return CabinetEmployee
     */
    protected function getCabinetOwner($object)
    {
        if($object instanceof CabinetCompany) {
            $employee = $object->employee;
        } elseif ($object instanceof CabinetCompanyUser) {
            if(!$company = $object->getCompany()) {
                return null;
            }
            $employee = $company->employee;
        } else {
            return null;
        }
        return $employee;
    }

    /**
     * @return mixed
     */
    public function getEmployee()
    {
        return $this->company ? $this->company->getEmployee() : null;
    }

}
