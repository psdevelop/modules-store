<?php

namespace app\modules\tickets\jobs;

use app\modules\tickets\dto\ChangeStatusTicketDTO;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\TicketService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class OnChangeStatusTicketJob extends BaseObject implements JobInterface
{
    /** @var ChangeStatusTicketDTO */
    public $changeStatusTicketDTO;

    public function execute($queue)
    {
        (Yii::$container->get(EnvironmentService::class))->setEnvironment($this->changeStatusTicketDTO->type);
        (Yii::$container->get(TicketService::class))->onChangedStatusTicket($this->changeStatusTicketDTO);
    }
}