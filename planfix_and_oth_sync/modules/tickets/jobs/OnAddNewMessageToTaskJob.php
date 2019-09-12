<?php

namespace app\modules\tickets\jobs;

use app\modules\tickets\dto\AddNewMessageToTaskDTO;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\SyncMessageService;
use app\modules\tickets\services\TaskMessageService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class OnAddNewMessageToTaskJob extends BaseObject implements JobInterface
{
    /** @var AddNewMessageToTaskDTO */
    public $addNewMessageToTaskDTO;

    public function execute($queue)
    {
        (Yii::$container->get(EnvironmentService::class))->setEnvironment($this->addNewMessageToTaskDTO->type);
        (Yii::$container->get(TaskMessageService::class))->onAddNewMessageToTask($this->addNewMessageToTaskDTO->messageId);
    }
}