<?php

namespace app\modules\tickets\repositories\contracts;

use app\modules\tickets\models\cabinet\ExternalTicketMessage;
use DateTimeImmutable;

interface ExternalCabinetMessagesRepositoryInterface
{
    /**
     * @param DateTimeImmutable $dateFrom
     * @return ExternalTicketMessage[]
     */
    public function findFromDate(DateTimeImmutable $dateFrom): array;

    public function getById(int $id): ExternalTicketMessage;

    public function save(ExternalTicketMessage $externalTicketMessage);

    public function deleteById(int $id);
}
