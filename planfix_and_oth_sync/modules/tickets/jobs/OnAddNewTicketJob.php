<?php

namespace app\modules\tickets\jobs;

use app\modules\tickets\dto\AddNewTicketDTO;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\TicketService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class OnAddNewTicketJob extends BaseObject implements JobInterface
{
    /** @var AddNewTicketDTO */
    public $addNewTicketDTO;

    public function execute($queue)
    {
        (Yii::$container->get(EnvironmentService::class))->setEnvironment($this->addNewTicketDTO->type);
        (Yii::$container->get(TicketService::class))->onAddNewTicket($this->addNewTicketDTO->ticketId);

    }
}