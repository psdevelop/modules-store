<?php

namespace app\modules\tickets\repositories\contracts;

use app\modules\tickets\models\cabinet\ExternalTicket;
use DateTimeImmutable;

interface ExternalCabinetTicketRepositoryInterface
{
    /**
     * @param DateTimeImmutable $dateFrom
     * @return ExternalTicket[]
     */
    public function findFromDate(DateTimeImmutable $dateFrom): array;

    public function updateStatus(int $id, string $status);

    public function getById(int $id): ExternalTicket;

    public function setDateTimeModified(int $id, string $dateTime);

    public function setPlanfixTaskId(int $id, int $planfixTaskId);
}
