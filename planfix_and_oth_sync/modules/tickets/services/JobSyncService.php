<?php

namespace app\modules\tickets\services;

use app\modules\tickets\repositories\ApiPlanfixTaskMessageRepository;
use app\modules\tickets\repositories\ApiPlanfixTaskRepository;
use app\modules\tickets\repositories\ARSyncCabinetMessageRepository;
use app\modules\tickets\repositories\ARSyncCabinetTicketRepository;
use app\modules\tickets\repositories\ARSyncUsersRepository;
use app\modules\tickets\repositories\contracts\ExternalCabinetMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\ExternalCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixTaskRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetMessageRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use app\modules\tickets\repositories\DBBlackExternalCabinetMessagesRepository;
use app\modules\tickets\repositories\DBBlackExternalCabinetTicketRepository;
use app\modules\tickets\repositories\DBLeadsExternalCabinetMessagesRepository;
use app\modules\tickets\repositories\DBLeadsExternalCabinetTicketRepository;
use Yii;

class JobSyncService
{
    public function addJob(string $jobClassName, array $params = [])
    {
        $log = sprintf('job: %s, params: %s', $jobClassName, print_r($params, true));
        Yii::info($log);

        Yii::$app->queue->push(new $jobClassName($params));
    }
}