<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 21.04.17
 * Time: 18:20
 */

namespace app\commands;

use app\exceptions\SyncException;
use app\components\filters\UniqueAccess;
use app\components\helpers\LogHelper;
use app\models\cabinet\CabinetAccountNote;
use app\models\cabinet\CabinetChat;
use app\models\cabinet\CabinetCompany;
use app\models\cabinet\CabinetCompanyUser;
use app\models\planfix\PlanfixBase;
use app\models\sync\SyncPlanfixChats;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixNotes;
use app\models\sync\SyncPlanfixUsers;

/**
 * Class SynchronizeCabinetController
 * @package app\commands
 */
class SyncCabinetController extends SyncBaseController
{
    public $syncType = 'sync_cabinet';

    /**
     * Behaviors - and that's it!
     * @return array
     */
    public function behaviors()
    {
        return [
            'UniqueAccess' => [
                'class' => UniqueAccess::class,
            ]
        ];
    }

    /**
     * Синхронизация без обновлений промежуточных таблиц
     * @return bool
     * @throws \Exception
     */
    public function actionRunForceUpdate()
    {
        LogHelper::initLog($this->syncType);
        try {
            \Yii::$app->cache->flush();
            LogHelper::info('Start--------------------');
            $this->loadJoined();
        } catch (\Exception $e) {
            LogHelper::action($e->getMessage());
            throw $e;
        }
        return true;
    }

    /**
     * ЗАПУСК СИНХРОНИЗАЦИИ
     * @param null $dateFrom
     * @param null $dateTo
     * @return bool
     * @throws \Exception
     */
    public function actionRun($dateFrom = null, $dateTo = null)
    {
        LogHelper::initLog($this->syncType);
        $this->setSyncType($this->syncType);
        $this->openSyncLog($dateFrom, $dateTo);

        try {
            \Yii::$app->cache->delete('planfixSid');
            LogHelper::info('-----------Start Sync Cabinet---------');
            $result = $this->runJoinAndLoad();
        } catch (\Exception $e) {
            $this->closeSyncLog(false, $e->getMessage());
            LogHelper::action($this->syncLog->message);
            throw $e;
        }

        $this->closeSyncLog($result);
        return true;
    }

    /**
     * ПЕРЕЗАПУСК СИНХРОНИЗАЦИИ
     * @return bool
     * @throws \Exception
     */
    public function actionRunForce()
    {
        return $this->actionRun(
            date('Y-m-d H:i:s', 0),
            date('Y-m-d H:i:s', time())
        );
    }

    /**
     * Принудительно обновление синхронизаци
     * @param $entity
     */
    public function actionForceUpdate($entity)
    {
        switch ($entity) {
            case 'companies':
                SyncPlanfixCompanies::forceUpdate();
                break;
            case 'users':
                SyncPlanfixUsers::forceUpdate();
                break;
            case 'notes':
                SyncPlanfixNotes::forceUpdate();
                break;
            case 'chats':
                SyncPlanfixChats::forceUpdate();
                break;
            default:
                LogHelper::info("Available entities for force update: companies, users, notes, chats, tickets.");
        }
    }

    /**
     * Обновление таблиц синхронизации и загрузка
     * @return bool
     * @throws SyncException
     */
    protected function runJoinAndLoad()
    {
        if (!$this->dateFrom || !$this->dateTo) {
            throw new SyncException("Неверные параметры времени запуска");
        }
        return $this->runJoin($this->dateFrom, $this->dateTo) && $this->loadJoined();
    }

    /**
     * Обновление таблиц синхронизации
     *  - Отслеживание изменений в таблицах Кабинетов по базам Leads / TradeLeads
     *  - Слияние результатов изменений из баз
     * @param string $dateFrom - datetime
     * @param string $dateTo - datetime
     * @return bool
     */
    protected function runJoin($dateFrom, $dateTo)
    {

        // Fix to sync Advertiser
        SyncPlanfixCompanies::updateSync(CabinetCompany::getAllJoinedAffiliates($dateFrom, $dateTo));
        // Fix to sync Affiliates
        SyncPlanfixCompanies::updateSync(CabinetCompany::getAllJoinedAdvertisers($dateFrom, $dateTo));
        // Fix to sync Affiliate`s Users
        SyncPlanfixUsers::updateSync(CabinetCompanyUser::getAllJoinedAffiliateUsers($dateFrom, $dateTo));
        // Fix to sync Advertiser`s Users
        SyncPlanfixUsers::updateSync(CabinetCompanyUser::getAllJoinedAdvertiserUsers($dateFrom, $dateTo));
        // Fix to sync Notes
        SyncPlanfixNotes::updateSync(CabinetAccountNote::getAllJoinedNotes($dateFrom, $dateTo));
        // Fix to sync Chats
        SyncPlanfixChats::updateSync(CabinetChat::getAllJoinedChats($dateFrom, $dateTo));
        // Success exit
        return true;
    }

    /**
     * Загрузка в Планфикс по таблице синхронизации + Актцализация таблиц синхронизации
     */
    protected function loadJoined()
    {
        // Planfix Instance
        $planfixBase = PlanfixBase::instance();
        // Get Planfix Users
        $planfixBase->getAllPlanfixUsers();
        // Add Companies
        $planfixBase->addContacts(SyncPlanfixCompanies::toAdd(), 'Company');
        // Update Companies
        $planfixBase->updateContacts(SyncPlanfixCompanies::toUpdate(), 'Company');
        // Add Company Users
        $planfixBase->addContacts(SyncPlanfixUsers::toAdd(), 'User');
        // Update Company Users
        $planfixBase->updateContacts(SyncPlanfixUsers::toUpdate(), 'User');
        // Add Notes
        $planfixBase->addNotes(SyncPlanfixNotes::toAdd());
        // Update Notes
        $planfixBase->updateNotes(SyncPlanfixNotes::toUpdate());
        // Add chats
        $planfixBase->addChats(SyncPlanfixChats::toAdd());
        // Update chats
        $planfixBase->updateChats(SyncPlanfixChats::toUpdate());

        // Success exit
        return true;
    }
}
