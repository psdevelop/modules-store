<?php

namespace app\modules\tickets\jobs;

use app\modules\tickets\dto\UpdateMessageInTicketDTO;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\TicketMessageService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class OnUpdateTicketMessageJob extends BaseObject implements JobInterface
{
    /** @var UpdateMessageInTicketDTO */
    public $updateMessageInTicketDTO;

    public function execute($queue)
    {
        (Yii::$container->get(EnvironmentService::class))->setEnvironment($this->updateMessageInTicketDTO->type);
        (Yii::$container->get(TicketMessageService::class))->updateMessageToSyncAndPlanfix($this->updateMessageInTicketDTO->messageId);
    }
}