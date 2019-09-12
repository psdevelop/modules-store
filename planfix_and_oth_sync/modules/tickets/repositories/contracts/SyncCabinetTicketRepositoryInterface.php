<?php

namespace app\modules\tickets\repositories\contracts;

use app\modules\tickets\models\sync\SyncCabinetTickets;
use DateTimeImmutable;

interface SyncCabinetTicketRepositoryInterface
{
    public function getDateLastSyncTicket(): DateTimeImmutable;

    /**
     * @param int[] $ids
     * @return SyncCabinetTickets[]
     */
    public function findByTicketIds(array $ids = []): array;

    public function getByTicketId(int $ticketId): SyncCabinetTickets;

    public function getByTaskId(int $taskId): SyncCabinetTickets;

    /**
     * @param string $date
     * @return SyncCabinetTickets[]
     */
    public function findModifiedAndCreatedFromDate(string $date): array;

    public function save(SyncCabinetTickets $internalCabinetTickets);
}
