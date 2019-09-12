<?php

namespace app\modules\tickets\services;

use app\modules\tickets\dto\AddNewTicketDTO;
use app\modules\tickets\dto\ChangeStatusTaskDTO;
use app\modules\tickets\dto\ChangeStatusTicketDTO;
use app\modules\tickets\enum\PlanfixTaskStatusEnum;
use app\modules\tickets\enum\TicketCategoryEnum;
use app\modules\tickets\enum\TicketCategorySubEnum;
use app\modules\tickets\helpers\MapperTicketPlanfix;
use app\modules\tickets\models\cabinet\ExternalTicket;
use app\modules\tickets\models\planfix\ExternalPlanfixTask;
use app\modules\tickets\models\sync\SyncCabinetTickets;
use app\modules\tickets\repositories\AbstractDBExternalAgencyCabinetRepository;
use app\modules\tickets\repositories\contracts\ExternalCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixTaskRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use app\modules\tickets\services\exceptions\ServiceException;
use app\modules\tickets\services\filters\FilterTicketService;
use Throwable;
use Yii;
use yii\helpers\ArrayHelper;

class TaskService extends BaseService
{
    /** @var ExternalCabinetTicketRepositoryInterface */
    private $externalCabinetTickets;

    /** @var SyncCabinetTicketRepositoryInterface */
    private $syncCabinetTickets;

    /** @var PlanfixTaskRepositoryInterface */
    private $planfixTasks;

    /**
     * @var AbstractDBExternalAgencyCabinetRepository
     */
    private $agencyCabinetRepository;

    public function __construct(
        ExternalCabinetTicketRepositoryInterface $externalCabinetTicketRepository,
        SyncCabinetTicketRepositoryInterface $syncCabinetTicketRepository,
        PlanfixTaskRepositoryInterface $planfixTaskRepository,
        AbstractDBExternalAgencyCabinetRepository $agencyCabinetRepository
    ) {
        $this->externalCabinetTickets = $externalCabinetTicketRepository;
        $this->syncCabinetTickets = $syncCabinetTicketRepository;
        $this->planfixTasks = $planfixTaskRepository;
        $this->agencyCabinetRepository = $agencyCabinetRepository;

        parent::__construct();
    }

    public function onChangedStatusTask(int $taskId)
    {
        try {
            $syncCabinetTicket = $this->syncCabinetTickets->getByTaskId($taskId);
            $externalPlanfixTask = $this->planfixTasks->getById($syncCabinetTicket->planfix_id);

            $this->transactionStart();

            $this->externalCabinetTickets->updateStatus(
                $syncCabinetTicket->ticket_id,
                MapperTicketPlanfix::getStatusTicket($externalPlanfixTask->status)
            );
            $externalTicket = $this->externalCabinetTickets->getById($syncCabinetTicket->ticket_id);

            $syncCabinetTicket->ticket_created = $externalTicket->created;
            $syncCabinetTicket->ticket_modified = $externalTicket->modified;
            $syncCabinetTicket->planfix_task_hash = $externalPlanfixTask->getHashForSync();

            $this->activateAgencyCabinetIfNeeded($externalPlanfixTask, $externalTicket);

            $this->syncCabinetTickets->save($syncCabinetTicket);

            $this->transactionCommit();
        } catch (Throwable $exception) {
            $this->transactionRollback();

            Yii::error($exception);
            throw new ServiceException('Update task with id: ' . $taskId . ' has error');
        }
    }

    /**
     * @param ExternalPlanfixTask $externalPlanfixTask
     * @param ExternalTicket $externalTicket
     */
    private function activateAgencyCabinetIfNeeded(ExternalPlanfixTask $externalPlanfixTask, ExternalTicket $externalTicket)
    {
        if ($externalTicket->category != TicketCategoryEnum::CATEGORY_AK) {
            return;
        }
        
        if ($externalTicket->subcategory != TicketCategorySubEnum::SUB_CREATE) {
            return;
        }
        
        if (PlanfixTaskStatusEnum::STATUS_COMPLETED == $externalPlanfixTask->status) {
            $agencyCabinetId = $externalTicket->additionalInformation->cabinetInfo->id;
            $this->agencyCabinetRepository->activateAgencyCabinet($agencyCabinetId);
        }
    }
}