<?php

namespace app\modules\tickets\services;

use app\modules\tickets\dto\AddNewMessageToTaskDTO;
use app\modules\tickets\dto\AddNewMessageToTicketDTO;
use app\modules\tickets\enum\TicketAccountTypeEnum;
use app\modules\tickets\helpers\CorrectTimeHelper;
use app\modules\tickets\models\cabinet\ExternalTicketMessage;
use app\modules\tickets\models\planfix\ExternalPlanfixTaskMessage;
use app\modules\tickets\models\sync\SyncCabinetTicketMessage;
use app\modules\tickets\repositories\contracts\ExternalCabinetMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\ExternalCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetMessageRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use app\modules\tickets\services\exceptions\ServiceException;
use app\modules\tickets\services\filters\FilterTicketMessagesService;
use DateTimeImmutable;
use Throwable;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class TaskMessageService extends BaseService
{
    const ACCAUNT_PLANFIX_ID = 1;

    const INTERVAL_UPDATE_FROM_PLANFIX = 604800; // 1 week

    /** @var SyncCabinetTicketRepositoryInterface */
    private $syncCabinetTickets;

    /** @var ExternalCabinetMessagesRepositoryInterface */
    private $externalCabinetMessages;

    /** @var ExternalCabinetTicketRepositoryInterface */
    private $externalCabinetTicket;

    /** @var SyncCabinetMessageRepositoryInterface */
    private $syncCabinetMessages;

    /** @var PlanfixMessagesRepositoryInterface */
    private $planfixMessages;

    /** @var FilterTicketMessagesService */
    private $filterTicketMessagesService;

    public function __construct(
        ExternalCabinetMessagesRepositoryInterface $externalCabinetMessagesRepository,
        ExternalCabinetTicketRepositoryInterface $externalCabinetTicketRepository,
        SyncCabinetMessageRepositoryInterface $syncCabinetMessageRepository,
        PlanfixMessagesRepositoryInterface $planfixMessagesRepository,
        SyncCabinetTicketRepositoryInterface $syncCabinetTicketRepository,
        FilterTicketMessagesService $filterTicketMessagesService
    ) {
        $this->externalCabinetMessages = $externalCabinetMessagesRepository;
        $this->externalCabinetTicket = $externalCabinetTicketRepository;
        $this->syncCabinetMessages = $syncCabinetMessageRepository;
        $this->planfixMessages = $planfixMessagesRepository;
        $this->syncCabinetTickets = $syncCabinetTicketRepository;
        $this->filterTicketMessagesService = $filterTicketMessagesService;

        parent::__construct();
    }

    public function onAddNewMessageToTask(int $messageId)
    {
        $lockService = new LockService(LockService::LOCK_MESSAGE);

        if ($lockService->lock(LockService::DEFAULT_TIMEOUT_SECONDS)) {

            if ($this->isMessageSynced($messageId)) {
                return;
            }

            try {
                $taskMessage = $this->planfixMessages->getById($messageId);
                $taskMessagesForSync = $this->filterTicketMessagesService->getMessagesByNotificationList([$taskMessage]);

                if (!count($taskMessagesForSync)) {
                    return;
                }

                $this->transactionStart();
                $this->addNewMessageToSyncAndCabinet($taskMessage);
                $this->transactionCommit();
            } catch (Throwable $exception) {
                $this->transactionRollback();
                Yii::error($exception);

                throw new ServiceException('onAddNewMessageToTask has error');
            }

            $lockService->unlock();
        }
    }

    public function onChangeMessageTask(int $messageId)
    {
        try {
            $taskMessage = $this->planfixMessages->getById($messageId);
            $taskMessagesForSync = $this->filterTicketMessagesService->getMessagesByNotificationList([$taskMessage]);
            $syncMessages = $this->syncCabinetMessages->findByPlanfixMessageIds([$taskMessage->id]);

            $this->transactionStart();

            if (! count($taskMessagesForSync) && count($syncMessages)) {
                $this->deleteMessageFromSyncAndCabinet($taskMessage);
            } elseif (! count($syncMessages) && count($taskMessagesForSync)) {
                $this->addNewMessageToSyncAndCabinet($taskMessage);
            } elseif (count($syncMessages) && count($taskMessagesForSync)) {
                $this->deleteMessageFromSyncAndCabinet($taskMessage);
                $this->addNewMessageToSyncAndCabinet($taskMessage);
            }

            $this->transactionCommit();
        } catch (Throwable $exception) {
            $this->transactionRollback();
            Yii::error($exception);

            throw new ServiceException('onChangeMessageTask has error');
        }
    }

    private function isMessageSynced(int $messageId): bool
    {
        $syncMessages = $this->syncCabinetMessages->findByPlanfixMessageIds([$messageId]);

        return count($syncMessages) > 0;
    }

    private function deleteMessageFromSyncAndCabinet(ExternalPlanfixTaskMessage $taskMessage)
    {
        $syncCabinetTicket = $this->syncCabinetMessages->getByPlanfixMessageId($taskMessage->id);
        $this->externalCabinetMessages->deleteById($syncCabinetTicket->ticket_message_id);
        $this->syncCabinetMessages->delete($syncCabinetTicket);
    }

    private function addNewMessageToSyncAndCabinet(ExternalPlanfixTaskMessage $taskMessage)
    {
        $syncCabinetTicket = $this->syncCabinetTickets->getByTaskId($taskMessage->taskId);

        $externalTicketMessage = new ExternalTicketMessage([
            'accountId' => self::ACCAUNT_PLANFIX_ID,
            'accountType' => TicketAccountTypeEnum::TYPE_MANAGER,
            'message' => ExternalTicketMessage::normalizeMessage($taskMessage->description),
            'ticketId' => $syncCabinetTicket->ticket_id,
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s'),
        ]);

        $this->externalCabinetMessages->save($externalTicketMessage);
        $this->externalCabinetTicket->setDateTimeModified(
            $externalTicketMessage->ticketId,
            CorrectTimeHelper::correctBeginDateTime(date('Y-m-d H:i:s'), -3)->format('Y-m-d H:i:s')
        );

        $syncCabinetTicketMessage = new SyncCabinetTicketMessage();

        $syncCabinetTicketMessage->ticket_message_id = $externalTicketMessage->id;
        $syncCabinetTicketMessage->ticket_message_created = $externalTicketMessage->created;
        $syncCabinetTicketMessage->ticket_message_modified = $externalTicketMessage->modified;
        $syncCabinetTicketMessage->planfix_message_id = $taskMessage->id;
        $syncCabinetTicketMessage->hash = $taskMessage->getHash();

        $this->syncCabinetMessages->save($syncCabinetTicketMessage);
    }
}
