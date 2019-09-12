<?php

namespace app\modules\tickets\services;

use app\modules\tickets\dto\ChangeStatusTicketDTO;
use app\modules\tickets\enum\TicketCategoryEnum;
use app\modules\tickets\helpers\MapperTicketPlanfix;
use app\modules\tickets\models\cabinet\ExternalTicket;
use app\modules\tickets\models\planfix\ExternalPlanfixTask;
use app\modules\tickets\models\sync\SyncCabinetTickets;
use app\modules\tickets\repositories\contracts\ExternalCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixContactRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixTaskRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use app\modules\tickets\services\exceptions\ServiceException;
use Throwable;
use Yii;
use yii\helpers\ArrayHelper;

class TicketService extends BaseService
{
    /** @var ExternalCabinetTicketRepositoryInterface */
    private $externalCabinetTickets;

    /** @var SyncCabinetTicketRepositoryInterface */
    private $syncCabinetTickets;

    /** @var PlanfixTaskRepositoryInterface */
    private $planfixTasks;

    /** @var SyncUsersRepositoryInterface */
    private $syncUsers;

    /** @var SyncUsersRepositoryInterface */
    private $planfixContacts;

    public function __construct(
        ExternalCabinetTicketRepositoryInterface $externalCabinetTicketRepository,
        SyncCabinetTicketRepositoryInterface $syncCabinetTicketRepository,
        PlanfixTaskRepositoryInterface $planfixTaskRepository,
        SyncUsersRepositoryInterface $syncUsersRepository,
        PlanfixContactRepositoryInterface $planfixContactRepository
    ) {
        $this->externalCabinetTickets = $externalCabinetTicketRepository;
        $this->syncCabinetTickets = $syncCabinetTicketRepository;
        $this->planfixTasks = $planfixTaskRepository;
        $this->syncUsers = $syncUsersRepository;
        $this->planfixContacts = $planfixContactRepository;

        parent::__construct();
    }

    /**
     * @param ChangeStatusTicketDTO $changeStatusDTO
     * @throws ServiceException
     */
    public function onChangedStatusTicket(ChangeStatusTicketDTO $changeStatusDTO)
    {
        $this->transactionStart();
        try {
            $externalTicket = $this->externalCabinetTickets->getById($changeStatusDTO->ticketId);
            $syncCabinetTicket = $this->syncCabinetTickets->getByTicketId($changeStatusDTO->ticketId);

            $this->planfixTasks->updateStatus(
                $syncCabinetTicket->planfix_id,
                MapperTicketPlanfix::getStatusTask($changeStatusDTO->status)
            );
            $savedPlanfixTask = $this->planfixTasks->getById($syncCabinetTicket->planfix_id);

            $syncCabinetTicket->ticket_created = $externalTicket->created;
            $syncCabinetTicket->ticket_modified = $externalTicket->modified;
            $syncCabinetTicket->planfix_id = $savedPlanfixTask->id;
            $syncCabinetTicket->planfix_task_hash = $savedPlanfixTask->getHashForSync();

            $this->syncCabinetTickets->save($syncCabinetTicket);

            $this->transactionCommit();
        } catch (Throwable $exception) {
            $this->transactionRollback();

            Yii::error($exception);
            throw new ServiceException('sync tickets -> planfix has error ');
        }
    }

    /**
     * @param int $ticketId
     * @throws ServiceException
     */
    public function onAddNewTicket(int $ticketId)
    {
        $syncCabinetTickets = $this->syncCabinetTickets->findByTicketIds([$ticketId]);
        if (count($syncCabinetTickets)) {
            throw new ServiceException('Ticket was synced');
        }

        $this->transactionStart();
        try {
            $externalTicket = $this->externalCabinetTickets->getById($ticketId);
            $this->addNewExternalTicketToSyncAndPlanfix($externalTicket);
            $this->transactionCommit();
        } catch (Throwable $exception) {
            $this->transactionRollback();
            Yii::error($exception);
            throw new ServiceException('sync tickets -> planfix has error ');
        }
    }

    /**
     * @param ExternalTicket $externalTicket
     */
    private function addNewExternalTicketToSyncAndPlanfix(ExternalTicket $externalTicket)
    {
        $syncUser = $this->syncUsers->getByCabinetUserId($externalTicket->accountId);
        $externalPlanfixTaskWokers = $this->definePlanfixTaskWorkers($externalTicket, $syncUser->planfix_general_id);

        $externalPlanfixTask = ExternalPlanfixTask::getInstanceByExternalCabinetTicket(
            $externalTicket,
            $syncUser->planfix_id,
            $syncUser->planfix_userid,
            [],
            $externalPlanfixTaskWokers['workerUserIds'],
            $externalPlanfixTaskWokers['workerGroupIds']
        );

        $planfixTaskId = $this->planfixTasks->save($externalPlanfixTask);
        $savedPlanfixTask = $this->planfixTasks->getById($planfixTaskId);

        $syncCabinetTicket = new SyncCabinetTickets();

        $syncCabinetTicket->ticket_id = $externalTicket->id;
        $syncCabinetTicket->ticket_created = $externalTicket->created;
        $syncCabinetTicket->ticket_modified = $externalTicket->modified;
        $syncCabinetTicket->planfix_id = $savedPlanfixTask->id;
        $syncCabinetTicket->planfix_task_hash = $savedPlanfixTask->getHashForSync();

        $this->syncCabinetTickets->save($syncCabinetTicket);

        $this->externalCabinetTickets->setPlanfixTaskId($externalTicket->id, $savedPlanfixTask->general);
    }

    /**
     * @param ExternalTicket $externalTicket
     * @param int $SyncUserPlanfixGeneralId
     * @return array
     */
    private function definePlanfixTaskWorkers(ExternalTicket $externalTicket, int $SyncUserPlanfixGeneralId)
    {
        $workerUserIds = [];
        $workerGroupIds = [MapperTicketPlanfix::getDefaultUserGroupAsWorker(
            $externalTicket->project,
            $externalTicket->category,
            $externalTicket->subcategory
        )];

        if (in_array($externalTicket->category, TicketCategoryEnum::getCategoriesWithWorkerUser())) {
            $syncUserPlanfixContact = $this->planfixContacts->getByPlanfixGeneralId($SyncUserPlanfixGeneralId);
            $responsibleUserId = $syncUserPlanfixContact->getResponsibleUserId();

            if ($responsibleUserId !== null) {
                $workerUserIds = [$responsibleUserId];
                $workerGroupIds = [];
            }
        }

        return [
          'workerUserIds' => $workerUserIds,
          'workerGroupIds' => $workerGroupIds,
        ];
    }
}