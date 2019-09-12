<?php

namespace app\modules\tickets\repositories\contracts;

use app\modules\tickets\models\sync\SyncCabinetTicketMessage;
use DateTimeImmutable;

interface SyncCabinetMessageRepositoryInterface
{
    public function getDateLastSyncMessage(): DateTimeImmutable;

    /**
     * @param int[] $ids
     * @return SyncCabinetTicketMessage[]
     */
    public function findByTicketMessageIds(array $ids = []): array;

    /**
     * @param int $cabinetMessageId
     * @return SyncCabinetTicketMessage
     */
    public function findPlanfixCommentIdByTicketMessageId(int $cabinetMessageId) : SyncCabinetTicketMessage;

    /**
     * @param int[] $ids
     * @return SyncCabinetTicketMessage[]
     */
    public function findByPlanfixMessageIds(array $ids = []): array;

    /**
     * @param string $date
     * @return SyncCabinetTicketMessage[]
     */
    public function findModifiedAndCreatedFromDate(string $date): array;

    public function save(SyncCabinetTicketMessage $cabinetTicketMessage);

    public function getByPlanfixMessageId(int $planfixMessageId): SyncCabinetTicketMessage;

    public function delete(SyncCabinetTicketMessage $syncCabinetTicketMessage);

}
