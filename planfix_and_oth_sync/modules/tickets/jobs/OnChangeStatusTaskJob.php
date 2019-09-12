<?php

namespace app\modules\tickets\jobs;

use app\modules\tickets\dto\ChangeStatusTaskDTO;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\SyncTicketService;
use app\modules\tickets\services\TaskService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class OnChangeStatusTaskJob extends BaseObject implements JobInterface
{
    /** @var ChangeStatusTaskDTO */
    public $changeStatusTaskDTO;

    public function execute($queue)
    {
        (Yii::$container->get(EnvironmentService::class))->setEnvironment($this->changeStatusTaskDTO->type);
        (Yii::$container->get(TaskService::class))->onChangedStatusTask($this->changeStatusTaskDTO->taskId);
    }
}