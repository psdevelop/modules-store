<?php

namespace app\modules\tickets\services\filters;

use app\modules\tickets\models\cabinet\ExternalTicket;
use app\modules\tickets\models\planfix\ExternalPlanfixTask;
use app\modules\tickets\models\sync\SyncCabinetTickets;
use app\modules\tickets\repositories\contracts\PlanfixTaskRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use yii\helpers\ArrayHelper;

class FilterTicketService
{
    /** @var SyncCabinetTicketRepositoryInterface */
    private $syncCabinetTickets;

    /** @var PlanfixTaskRepositoryInterface */
    private $planfixTasks;

    public function __construct(
        SyncCabinetTicketRepositoryInterface $syncCabinetTicketRepository,
        PlanfixTaskRepositoryInterface $planfixTaskRepository
    ) {
        $this->syncCabinetTickets = $syncCabinetTicketRepository;
        $this->planfixTasks = $planfixTaskRepository;
    }

    /**
     * @param ExternalTicket[] $allExternalTickets
     * @return ExternalTicket[]
     */
    public function getNewExternalTickets(array $allExternalTickets): array
    {
        return $this->filter(
            $allExternalTickets,
            function ($idExternal, $idsSyncCabinetTickets) {
                return !in_array($idExternal, $idsSyncCabinetTickets);
            }
        );
    }

    /**
     * @param ExternalTicket[] $allExternalTickets
     * @param SyncCabinetTickets[] $syncCabinetTickets
     * @return ExternalTicket[]
     */
    public function getUpdatedExternalTickets(array $allExternalTickets): array
    {
        return $this->filter(
            $allExternalTickets,
            function ($idExternal, $idsSyncCabinetTickets) {
                return in_array($idExternal, $idsSyncCabinetTickets);
            }
        );
    }

    /**
     * @param SyncCabinetTickets[] $allSyncCabinetTickets
     * @return ExternalPlanfixTask[]
     */
    public function getSyncCabinetTicketsForUpdate(array $allSyncCabinetTickets)
    {
        $idsTaskForSync = ArrayHelper::getColumn($allSyncCabinetTickets, 'planfix_id');
        $syncCabinetTicketsWithIndex = ArrayHelper::index($allSyncCabinetTickets, 'planfix_id');
        $externalPlanfixTasks = $this->planfixTasks->findByIds($idsTaskForSync);

        $externalPlanfixTasksForSync = [];

        foreach ($externalPlanfixTasks as $externalPlanfixTask) {
            /** @var SyncCabinetTickets $syncCabinetTicket */
            $syncCabinetTicket = $syncCabinetTicketsWithIndex[$externalPlanfixTask->id];

            if ($externalPlanfixTask->getHashForSync() === $syncCabinetTicket->planfix_task_hash) {
                continue;
            }

            $externalPlanfixTasksForSync[] = $externalPlanfixTask;
        }

        return $externalPlanfixTasksForSync;
    }

    /**
     * @param ExternalTicket[] $externalTickets
     * @param callable $compareCallback
     * @return ExternalTicket[]
     */
    private function filter(array $externalTickets, callable $compareCallback): array
    {
        $syncCabinetTickets = $this->getSyncCabinetTickets($externalTickets);

        $idsExternalTicketsForSync = ArrayHelper::getColumn($externalTickets, 'id');
        $idsSyncCabinetTickets = ArrayHelper::getColumn($syncCabinetTickets, 'ticket_id');
        $modifiedExternalTicketsWithIndex = ArrayHelper::index($externalTickets, 'id');

        $filterTickets = [];

        foreach($idsExternalTicketsForSync as $idExternal) {
            if ($compareCallback($idExternal, $idsSyncCabinetTickets)) {
                $filterTickets[] = $modifiedExternalTicketsWithIndex[$idExternal];
            }
        }

        return $filterTickets;
    }

    /**
     *
     * @param ExternalTicket[] $externalTickets
     * @return SyncCabinetTickets[]
     */
    private function getSyncCabinetTickets(array $externalTickets)
    {
        $idsExternalTicketsForSync = ArrayHelper::getColumn($externalTickets, 'id');

        return $this->syncCabinetTickets->findByTicketIds($idsExternalTicketsForSync);
    }
}