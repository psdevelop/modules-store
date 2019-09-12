<?php

namespace app\modules\tickets\repositories;

use app\modules\tickets\models\sync\SyncCabinetTicketMessage;
use app\modules\tickets\models\sync\SyncCabinetTickets;
use app\modules\tickets\repositories\contracts\SyncCabinetMessageRepositoryInterface;
use app\modules\tickets\repositories\exceptions\EntityNotFoundException;
use app\modules\tickets\repositories\exceptions\EntitySaveErrorException;
use DateTimeImmutable;

class ARSyncCabinetMessageRepository implements SyncCabinetMessageRepositoryInterface
{
    const MIN_DATE = '2015-01-01 01:01:01';

    public function getDateLastSyncMessage(): DateTimeImmutable
    {
        $messageWithMaxDateCreated = SyncCabinetTicketMessage::find()->max('ticket_message_created') ?? self::MIN_DATE;
        $messageWithMaxDateModified = SyncCabinetTicketMessage::find()->max('ticket_message_modified') ?? self::MIN_DATE;

        $maxCreated = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $messageWithMaxDateCreated);
        $maxModified = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $messageWithMaxDateModified);

        $minDate = min($maxCreated, $maxModified);

        return $minDate->modify('-1 day');
    }

    /**
     * @param array $ids
     * @return SyncCabinetTicketMessage[]
     */
    public function findByTicketMessageIds(array $ids = []): array
    {
        return SyncCabinetTicketMessage::find()
            ->where(['ticket_message_id' => $ids])
            ->all();
    }

    /**
     * @param int $cabinetMessageId
     * @return SyncCabinetTicketMessage
     */
    public function findPlanfixCommentIdByTicketMessageId(int $cabinetMessageId): SyncCabinetTicketMessage
    {
        return SyncCabinetTicketMessage::find()
            ->where(['ticket_message_id' => $cabinetMessageId])
            ->one();
    }

    /**
     * @param int[] $ids
     * @return SyncCabinetTicketMessage[]
     */
    public function findByPlanfixMessageIds(array $ids = []): array
    {
        return SyncCabinetTicketMessage::find()
            ->where(['planfix_message_id' => $ids])
            ->all();
    }

    public function save(SyncCabinetTicketMessage $cabinetTicketMessage)
    {
        if (! $cabinetTicketMessage->save()) {
            throw new EntitySaveErrorException('SyncCabinetTicketMessage not saved');
        }
    }

    /**
     * @param string $date
     * @return SyncCabinetTicketMessage[]
     */
    public function findModifiedAndCreatedFromDate(string $date): array
    {
        return SyncCabinetTicketMessage::find()
            ->where([
                'and',
                ['>', 'ticket_message_created', $date],
                ['>', 'ticket_message_modified', $date]])
            ->all();
    }

    public function getByPlanfixMessageId(int $planfixMessageId): SyncCabinetTicketMessage
    {
        $syncCabinetTicketMessage = SyncCabinetTicketMessage::find()
            ->where(['planfix_message_id' => $planfixMessageId])
            ->one();

        if ($syncCabinetTicketMessage === null) {
            throw new EntityNotFoundException('SyncCabinetTicketMessage with planfix_message_id:' .  $planfixMessageId .' not found');
        }

        return $syncCabinetTicketMessage;
    }

    public function delete(SyncCabinetTicketMessage $cabinetTicketMessage)
    {
        $cabinetTicketMessage->delete();
    }
}