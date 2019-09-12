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
use app\models\cabinet\CabinetAffiliateBillingFastPayoutRequest;
use app\models\planfix\PlanfixBase;
use app\models\planfix\PlanfixPayoutTask;
use app\models\sync\SyncPlanfixPayments;
use yii\console\Exception;

class SyncPaymentsController extends SyncBaseController
{

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
     */
    public function actionRun()
    {
        LogHelper::initLog('sync_payments');
        $this->runJoinAndLoad();
    }

    /**
     * Обновление таблиц синхронизации и загрузка
     * @return bool
     * @throws Exception
     */
    protected function runJoinAndLoad()
    {
        return $this->runJoin() && $this->loadJoined();
    }

    /**
     * Обновление таблиц синхронизации
     *  - Отслеживание изменений в таблицах Кабинетов по базам Leads / TradeLeads
     *  - Слияние результатов изменений из баз
     * @return bool
     */
    public function runJoin()
    {
        SyncPlanfixPayments::updateSync(CabinetAffiliateBillingFastPayoutRequest::getAllNeedApprove());
        return true;
    }

    /**
     * Загрузка в Планфикс по таблице синхронизации + Актцализация таблиц синхронизации
     */
    public function loadJoined()
    {
        // Planfix Instance
        $planfixBase = PlanfixBase::instance();

        // Get Planfix Users
        $planfixBase->getAllPlanfixUsers();

        // Pull finalized Payout Requests
        PlanfixPayoutTask::instance()->sendPaymentsPlanfixToCabinet();
        // Add Payout Requests
        PlanfixPayoutTask::instance()->sendPaymentCabinetToPlanfix(SyncPlanfixPayments::toAdd());

        // Success exit
        return true;
    }

}
