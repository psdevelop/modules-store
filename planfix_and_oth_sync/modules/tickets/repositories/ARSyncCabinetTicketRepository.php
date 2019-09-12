<?php

namespace app\modules\tickets\repositories;

use app\modules\tickets\models\sync\SyncCabinetTickets;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\exceptions\EntitySaveErrorException;
use DateTimeImmutable;

class ARSyncCabinetTicketRepository implements SyncCabinetTicketRepositoryInterface
{
    const MIN_DATE = '2015-01-01 01:01:01';

    public function getDateLastSyncTicket(): DateTimeImmutable
    {
        $ticketWithMaxDateCreated = SyncCabinetTickets::find()->max('ticket_created') ?? self::MIN_DATE;
        $ticketWithMaxDateModified = SyncCabinetTickets::find()->max('ticket_modified') ?? self::MIN_DATE;

        $maxCreated = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ticketWithMaxDateCreated);
        $maxModified = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ticketWithMaxDateModified);

        $minDate = min($maxCreated, $maxModified);

        return $minDate->modify('-1 day');
    }

    /**
     * @param array $ids
     * @return SyncCabinetTickets[]
     */
    public function findByTicketIds(array $ids = []): array
    {
        return SyncCabinetTickets::find()
            ->where(['ticket_id' => $ids])
            ->all();
    }

    public function save(SyncCabinetTickets $internalCabinetTickets)
    {
        if (! $internalCabinetTickets->save()) {
            throw new EntitySaveErrorException('SyncCabinetTickets not saved');
        }
    }

    /**
     * @param string $date
     * @return SyncCabinetTickets[]
     */
    public function findModifiedAndCreatedFromDate(string $date): array
    {
        return SyncCabinetTickets::find()
            ->where([
                'and',
                ['>', 'ticket_created', $date],
                ['>', 'ticket_modified', $date]])
            ->all();
    }

    public function getByTaskId(int $taskId): SyncCabinetTickets
    {
         $syncCabinetTicket = SyncCabinetTickets::find()
            ->where(['planfix_id' => $taskId])
            ->one();

        if ($syncCabinetTicket === null) {
            throw new EntitySaveErrorException('SyncCabinetTickets with planfix_id:' . $taskId . ' not found');
        }

        return $syncCabinetTicket;
    }

    public function getByTicketId(int $ticketId): SyncCabinetTickets
    {
        $syncCabinetTicket = SyncCabinetTickets::find()
            ->where(['ticket_id' => $ticketId])
            ->one();

        if ($syncCabinetTicket === null) {
            throw new EntitySaveErrorException('SyncCabinetTickets with ticket_id:' . $ticketId . ' not found');
        }

        return $syncCabinetTicket;
    }
}