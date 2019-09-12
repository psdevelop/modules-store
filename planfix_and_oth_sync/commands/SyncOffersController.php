<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 26.05.17
 * Time: 16:53
 */

namespace app\commands;


use app\components\filters\UniqueAccess;
use app\components\helpers\LogHelper;
use app\exceptions\SyncException;
use app\models\cabinet\CabinetOffer;
use app\models\planfix\PlanfixBase;
use app\models\sync\SyncPlanfixOffers;

class SyncOffersController extends SyncBaseController
{
    public $syncType = 'offers';

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
            LogHelper::info('-----------Start Sync Offers---------');
            $result = $this->runJoinAndLoad();
        } catch (\Exception $e) {
            $this->closeSyncLog(false, $e->getMessage());
            // Rollback Users Notifications if crashed
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
        SyncPlanfixOffers::updateSync(CabinetOffer::getAllJoinedOffers($dateFrom, $dateTo));
        // Success exit
        return true;
    }

    public function loadJoined()
    {
        LogHelper::initLog('sync_offers');
        // Planfix Instance
        $planfixBase = PlanfixBase::instance();
        $planfixBase->addOffers(SyncPlanfixOffers::toAdd());
        $planfixBase->updateOffers(SyncPlanfixOffers::toUpdate());
        return true;
    }
}
