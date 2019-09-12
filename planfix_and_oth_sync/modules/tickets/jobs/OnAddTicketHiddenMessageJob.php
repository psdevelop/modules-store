<?php

namespace app\modules\tickets\jobs;

use app\modules\tickets\dto\AddHiddenMessageToTicketDTO;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\TicketMessageService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Класс отвечающий за вызов необходимого метода сервиса TicketMessageService при наступлении события AddTicketHiddenMessage
 * Class OnAddTicketHiddenMessageJob
 * @package app\modules\tickets\jobs
 */
class OnAddTicketHiddenMessageJob extends BaseObject implements JobInterface
{
    /** @var AddHiddenMessageToTicketDTO */
    public $addHiddenMessageToTicketDTO;

    /**
     * @param $queue
     */
    public function execute($queue)
    {
        (Yii::$container->get(EnvironmentService::class))->setEnvironment($this->addHiddenMessageToTicketDTO->type);
        (Yii::$container->get(TicketMessageService::class))->addHiddenMessageToTicket($this->addHiddenMessageToTicketDTO->messageText, $this->addHiddenMessageToTicketDTO->ticketId);
    }
}