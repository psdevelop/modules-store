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
 * Class CabinetChat
 * @property integer $id
 * @property string $created
 * @property string $external_id
 * @property string $account_type
 * @property integer $account_id
 * @property string $external_agent_id
 * @property string $external_user_name
 * @property string $chat_type
 * @property string $start_date
 * @property string $end_date
 * @property string $params
 *
 *
 * @property CabinetCompany $client
 *
 * @package app\models\cabinet
 */
class CabinetChat extends CabinetBase
{

    public static $table = 'chats';
    public static $createdField = 'created';
    public static $modifiedField = 'created';
    public $planfixTaskGeneral;
    public $planfixChatWorkers;
    public $planfixClientId;
    public $cabinetClient;
    public $planfixChatClient;
//    public $client;

    public static function getAllJoinedChats($dateFrom, $dateTo)
    {
        self::setDb('dbLeads');
        $connection = self::getDb();

        $table = self::$table;
        $leadsDb = DbHelper::getDbName(\Yii::$app->dbLeads);
        $tradeDb = DbHelper::getDbName(\Yii::$app->dbTradeLeads);
        $syncDb = DbHelper::getDbName(\Yii::$app->dbPlanfixSync);

        $leadsTable = $leadsDb . '.' . $table;
        $tradeTable = $tradeDb . '.' . $table;
        $syncTable = $syncDb . '.' . SyncBase::$syncChatsTable;

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
                $leadsTable.created BETWEEN '$dateFrom' AND  '$dateTo'
            ;"
        );
        $firstChunk = $command->queryAll();
        TimerHelper::timerStop(null,"Fetch Leads Chats","DB");
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
                $tradeTable.created BETWEEN '$dateFrom' AND  '$dateTo'
            ;"
        );
        $secondChunk = $command->queryAll();
        TimerHelper::timerStop(null,"Fetch TradeLeads Chats","DB");
        return array_merge($firstChunk, $secondChunk);
    }

    public function getClient()
    {
        $this->setDbById();
        $type = $this->account_type;
        /**
         * @var $relativeClass CabinetCompany
         */
        $relativeClass = str_replace('Base', ucfirst($type), CabinetBase::class);

        if ($type == 'guest') {
            $clientEmail = $this->extractEmail();
            if ($user = CabinetAffiliateUser::findOne(['email' => $clientEmail])) {
                return CabinetAffiliate::find()->where(['id' => $user->affiliate_id]);
            }

            if ($user = CabinetAdvertiserUser::findOne(['email' => $clientEmail])) {
                return CabinetAdvertiser::find()->where(['id' => $user->advertiser_id]);
            }
            return null;
        }

        if ($type != ContactTypesEnum::TYPE_AFFILIATE && $type != ContactTypesEnum::TYPE_ADVERTISER) {
            return null;
        }
        return $this->hasOne($relativeClass, ['id' => 'account_id']);
    }

    public function extractEmail()
    {
        $params = json_decode($this->params, true);

        if (!isset($params['visitor']['email']) || !$email = $params['visitor']['email']) {
            return null;
        }
        return $email;
    }

    public function extractName()
    {
        $params = json_decode($this->params, true);

        if (!isset($params['visitor']['name']) || !$name = $params['visitor']['name']) {
            return null;
        }
        return $name;
    }

    public function getAgent()
    {
        $this->setDbById();
        return $this->hasOne(CabinetChatExternalAgent::className(), ['external_id' => 'external_agent_id']);
    }

    public function getMessages()
    {
        $this->setDbById();
        return $this->hasMany(CabinetChatMessage::className(), ['chat_id' => 'id']);
    }

    public function getRealChatAccountType()
    {
        $accountType = 'guest';
        if ($this->account_type == 'guest') {
            if ($this->getClient() instanceof CabinetAffiliate) {
                $accountType = ContactTypesEnum::TYPE_AFFILIATE;
            }
            if ($this->getClient() instanceof CabinetAdvertiser) {
                $accountType = ContactTypesEnum::TYPE_ADVERTISER;
            }
        } else {
            $accountType = $this->account_type;
        }

        return $this->account_type = $accountType;
    }

}
