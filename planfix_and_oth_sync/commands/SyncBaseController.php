<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 31.05.17
 * Time: 18:00
 */

namespace app\commands;


use app\components\planfixWebService\PlanfixNotifications;
use app\models\planfix\PlanfixBase;
use app\models\sync\SyncPlanfixLog;
use yii\console\Controller;

class SyncBaseController extends Controller
{
    /* Дата начала периода синхронизации */
    public $dateFrom;
    /* Дата конца периода синхронизации */
    public $dateTo;

    /**
     * @var SyncPlanfixLog
     */
    public $syncLog;
    public $syncLogStart = null;
    public $syncType = null;

    public function setSyncType($syncType)
    {
        return $this->syncType = $syncType;
    }

    /**
     * Открыть запись в логе синхронизации
     * @param null $dateFrom
     * @param null $dateTo
     */
    public function openSyncLog($dateFrom = null, $dateTo = null)
    {
        $this->syncLog = new SyncPlanfixLog();
        $this->prepareSyncDates($dateFrom, $dateTo);
        $this->syncLogStart = microtime(true);
        $this->syncLog->date_from = $this->dateFrom;
        $this->syncLog->date_to = $this->dateTo;
        $this->syncLog->created = date('Y-m-d H:i:s', time());
        $this->syncLog->type = $this->syncType;
        $this->syncLog->save();
    }

    /**
     * Закрыть запись в логе синхронизации
     * @param $result
     * @param null $message
     */
    public function closeSyncLog($result, $message = null)
    {
        $this->syncLog->is_success = $result;
        $this->syncLog->message = $message;
        $this->syncLog->elapsed_time = (microtime(true) - $this->syncLogStart);
        $this->syncLog->save();
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     */
    public function prepareSyncDates($dateFrom = null, $dateTo = null)
    {
        $currentTime = time();
        $lastSync = SyncPlanfixLog::getLastSuccessSync($this->syncType);
        $newDateFrom = $dateFrom ? $dateFrom : date('Y-m-d H:i:s', $lastSync ? strtotime($lastSync->date_to) + 1 : 0);
        $newDateTo = $dateTo ? $dateTo : date('Y-m-d H:i:s', $currentTime);

        $this->dateFrom = $newDateFrom;
        $this->dateTo = $newDateTo;
    }

    /**
     * Выключить уведомления в Планфикс с фиксацией прежнего состояния
     */
    public function actionOffNotifications()
    {
        // Planfix Instance
        $planfixBase = PlanfixBase::instance();
        // Get Planfix Users
        $planfixBase->getAllPlanfixUsers();
        // Preset notifications component
        PlanfixNotifications::init();
        PlanfixNotifications::setReady([]);
        // Off users notifications
        PlanfixNotifications::setNewNotifications();
    }

    /**
     * Включить обратно
     */
    public function actionOnNotifications($file = null)
    {
        // Preset notifications component
        PlanfixNotifications::init();
        // Rollback Users Notifications
        PlanfixNotifications::setCurrentNotifications($file);
    }

    public function actionDumpNotifications($file = null)
    {
        // Planfix Instance
        $planfixBase = PlanfixBase::instance();
        // Get Planfix Users
        $planfixBase->getAllPlanfixUsers();
        // Preset notifications component
        PlanfixNotifications::init();
        PlanfixNotifications::fixNotifications($file);
    }
}
