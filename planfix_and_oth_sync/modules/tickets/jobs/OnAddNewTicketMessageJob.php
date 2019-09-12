<?php

namespace app\modules\tickets\jobs;

use app\modules\tickets\dto\AddNewMessageToTicketDTO;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\TicketMessageService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class OnAddNewTicketMessageJob extends BaseObject implements JobInterface
{
    /** @var AddNewMessageToTicketDTO */
    public $addNewMessageToTicketDTO;

    public function execute($queue)
    {
        (Yii::$container->get(EnvironmentService::class))->setEnvironment($this->addNewMessageToTicketDTO->type);
        (Yii::$container->get(TicketMessageService::class))->addMessageToSyncAndPlanfix($this->addNewMessageToTicketDTO->messageId);
    }
}