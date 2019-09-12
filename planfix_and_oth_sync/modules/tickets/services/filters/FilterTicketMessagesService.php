<?php

namespace app\modules\tickets\services\filters;


use app\modules\tickets\models\cabinet\ExternalTicketMessage;
use app\modules\tickets\models\planfix\ExternalPlanfixTaskMessage;
use app\modules\tickets\repositories\contracts\SyncCabinetMessageRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use yii\helpers\ArrayHelper;

class FilterTicketMessagesService
{
    /** @var SyncCabinetMessageRepositoryInterface */
    private $syncCabinetMessages;

    /** @var SyncUsersRepositoryInterface */
    private $syncUsers;

    public function __construct(
        SyncCabinetMessageRepositoryInterface $syncCabinetMessageRepository,
        SyncUsersRepositoryInterface $syncUsersRepository
    ) {
        $this->syncCabinetMessages = $syncCabinetMessageRepository;
        $this->syncUsers = $syncUsersRepository;
    }

    /**
     * @param ExternalTicketMessage[] $modifiedExternalMessages
     * @return ExternalTicketMessage[]
     */
    public function getNewExternalMessages(array $modifiedExternalMessages): array
    {
        $idsExternalMessagesForSync = ArrayHelper::getColumn($modifiedExternalMessages, 'id');
        $syncCabinetMessages = $this->syncCabinetMessages->findByTicketMessageIds($idsExternalMessagesForSync);

        $idsExternalMessagesForSync = ArrayHelper::getColumn($modifiedExternalMessages, 'id');
        $idsSyncCabinetMessages = ArrayHelper::getColumn($syncCabinetMessages, 'ticket_message_id');
        $modifiedExternalMessagesWithIndex = ArrayHelper::index($modifiedExternalMessages, 'id');

        $newMessages = [];

        foreach($idsExternalMessagesForSync as $idExternal) {
            if (in_array($idExternal, $idsSyncCabinetMessages)) {
                continue;
            }

            $newMessages[] = $modifiedExternalMessagesWithIndex[$idExternal];
        }

        return $newMessages;
    }

    /**
     * @param ExternalPlanfixTaskMessage[] $taskMessages
     * @return ExternalPlanfixTaskMessage[]
     */
    public function getMessagesByNewSign(array $taskMessages): array
    {
        $result = [];

        $idsMessages = ArrayHelper::getColumn($taskMessages, 'id');
        $taskMessagesWithIndex = ArrayHelper::index($taskMessages, 'id');
        $chunksIdsMessages = array_chunk($idsMessages, 1000); // 1000 - size chunk ids
        $idsNotSync = [];

        foreach ($chunksIdsMessages as $chunkIds) {
            $syncMessages = $this->syncCabinetMessages->findByPlanfixMessageIds($chunkIds);
            $idsSyncMessages = ArrayHelper::getColumn($syncMessages, 'planfix_message_id');

            $idsDiff = array_diff($chunkIds, $idsSyncMessages);

            if (! count($idsDiff)) {
                continue;
            }

            $idsNotSync = array_merge($idsNotSync, $idsDiff);
        }

        foreach ($idsNotSync as $id) {
            $result[] = $taskMessagesWithIndex[$id];
        }

        return $result;
    }

    /**
     * @param ExternalPlanfixTaskMessage[] $taskMessages
     * @return ExternalPlanfixTaskMessage[]
     */
    public function getChangedMessages(array $taskMessages): array
    {
        $idsMessages = ArrayHelper::getColumn($taskMessages, 'id');
        $taskMessagesWithIndex = ArrayHelper::index($taskMessages, 'id');
        $chunksIdsMessages = array_chunk($idsMessages, 1000); // 1000 - size chunk ids

        $idsUpdated = [];

        foreach ($chunksIdsMessages as $chunkIds) {
            $syncMessages = $this->syncCabinetMessages->findByPlanfixMessageIds($chunkIds);

            foreach ($syncMessages as $syncMessage) {
                if ($syncMessage->hash === $taskMessagesWithIndex[$syncMessage->planfix_message_id]->getHash()) {
                    continue;
                }

                $idsUpdated[] = $taskMessagesWithIndex[$syncMessage->planfix_message_id];
            }

        }

        return $idsUpdated;
    }

    /**
     * @param ExternalPlanfixTaskMessage[] $taskPlanfixMessages
     * @return ExternalPlanfixTaskMessage[]
     */
    public function getMessagesByNotificationList(array $taskPlanfixMessages): array
    {
        $result = [];

        $notifiedListUserIds = $this->extractNotifiedListUserIds($taskPlanfixMessages);
        $allSyncUsers = $this->syncUsers->findAllByPlanfixIdsWithIndex($notifiedListUserIds);

        foreach ($taskPlanfixMessages as $taskMessage) {
            if (! count($taskMessage->notifiedListUserIds)) {
                continue;
            }

            foreach ($taskMessage->notifiedListUserIds as $notifiedListUserId) {
                if (isset($allSyncUsers[$notifiedListUserId])) {
                    $result[] = $taskMessage;
                    continue;
                }
            }
        }

        return $result;
    }

    /**
     * @param ExternalPlanfixTaskMessage[] $taskMessages
     * @return int[]
     */
    private function extractNotifiedListUserIds(array $taskMessages): array
    {
        $result = [];

        foreach ($taskMessages as $taskMessage) {
            if (! count($taskMessage->notifiedListUserIds)) {
                continue;
            }

            $result = ArrayHelper::merge($result, $taskMessage->notifiedListUserIds);
        }

        return array_unique($result);
    }
}
