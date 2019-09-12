<?php

namespace app\modules\tickets\services;

use app\modules\tickets\dto\AddNewTicketDTO;
use app\modules\tickets\dto\ChangeStatusTaskDTO;
use app\modules\tickets\dto\ChangeStatusTicketDTO;
use app\modules\tickets\helpers\MapperTicketPlanfix;
use app\modules\tickets\jobs\OnAddNewTicketJob;
use app\modules\tickets\jobs\OnChangeStatusTaskJob;
use app\modules\tickets\models\cabinet\ExternalTicket;
use app\modules\tickets\models\planfix\ExternalPlanfixTask;
use app\modules\tickets\models\sync\SyncCabinetTickets;
use app\modules\tickets\repositories\contracts\ExternalCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixTaskRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use app\modules\tickets\services\exceptions\ServiceException;
use app\modules\tickets\services\filters\FilterTicketService;
use Throwable;
use Yii;
use yii\helpers\ArrayHelper;

class   SyncTicketService extends BaseService
{
    const INTERVAL_UPDATE_FROM_PLANFIX = 604800; // 1 week

    /** @var ExternalCabinetTicketRepositoryInterface */
    private $externalCabinetTickets;

    /** @var SyncCabinetTicketRepositoryInterface */
    private $syncCabinetTickets;

    /** @var FilterTicketService */
    private $filterTicketService;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        ExternalCabinetTicketRepositoryInterface $externalCabinetTicketRepository,
        SyncCabinetTicketRepositoryInterface $syncCabinetTicketRepository,
        PlanfixTaskRepositoryInterface $planfixTaskRepository,
        SyncUsersRepositoryInterface $syncUsersRepository,
        FilterTicketService $filterTicketService,
        JobSyncService $jobSyncService
    ) {
        $this->externalCabinetTickets = $externalCabinetTicketRepository;
        $this->syncCabinetTickets = $syncCabinetTicketRepository;
        $this->filterTicketService = $filterTicketService;
        $this->jobSyncService = $jobSyncService;

        parent::__construct();
    }

    public function syncTicketToPlanfix()
    {
        try {
            $newExternalTicketsForSync = $this->findNewTicketForSync();

            foreach ($newExternalTicketsForSync as $newExternalTicket) {
                $addNewTicketDTO = new AddNewTicketDTO();

                $addNewTicketDTO->type = (Yii::$container->get(EnvironmentService::class))->getProjectEnvironment();
                $addNewTicketDTO->ticketId = $newExternalTicket->id;
                $this->jobSyncService->addJob(
                    OnAddNewTicketJob::class,
                    ['addNewTicketDTO' => $addNewTicketDTO]
                );
            }
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw new ServiceException('sync tickets -> planfix has error ');
        }
    }

    public function syncPlanfixToTicket()
    {
        try {
            $syncCabinetTicketsForUpdate = $this->findUpdatedTaskForSync();

            foreach ($syncCabinetTicketsForUpdate as $externalPlanfixTask) {
                $changeStatusTaskDTO = new ChangeStatusTaskDTO();

                $changeStatusTaskDTO->type = (Yii::$container->get(EnvironmentService::class))->getProjectEnvironment();
                $changeStatusTaskDTO->taskId = $externalPlanfixTask->id;

                $this->jobSyncService->addJob(
                    OnChangeStatusTaskJob::class,
                    ['changeStatusTaskDTO' => $changeStatusTaskDTO]
                );
            }
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw new ServiceException('sync planfix -> tickets has error');
        }
    }

    /**
     * @return ExternalTicket[]
     */
    private function findNewTicketForSync(): array
    {
        $dateFrom = $this->syncCabinetTickets->getDateLastSyncTicket();
        $externalTickets = $this->externalCabinetTickets->findFromDate($dateFrom);

        if (count($externalTickets) === 0) {
            return [];
        }

        return $this->filterTicketService->getNewExternalTickets($externalTickets);
    }

    /**
     * @return ExternalPlanfixTask[]
     */
    private function findUpdatedTaskForSync(): array
    {
        $dateLastSync = $this->syncCabinetTickets->getDateLastSyncTicket();
        $dateFrom =  date('Y-m-d H:i:s', $dateLastSync->getTimestamp() - self::INTERVAL_UPDATE_FROM_PLANFIX);
        $syncCabinetTickets = $this->syncCabinetTickets->findModifiedAndCreatedFromDate($dateFrom);

        if (! count($syncCabinetTickets)) {
            return [];
        }

        return $this->filterTicketService->getSyncCabinetTicketsForUpdate($syncCabinetTickets);
    }
}
