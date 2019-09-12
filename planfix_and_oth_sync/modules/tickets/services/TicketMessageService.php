<?php

namespace app\modules\tickets\services;

use app\modules\tickets\models\planfix\ExternalPlanfixTaskMessage;
use app\modules\tickets\models\sync\SyncCabinetTicketMessage;
use app\modules\tickets\repositories\contracts\ExternalCabinetMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetMessageRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use app\modules\tickets\services\exceptions\ServiceException;
use Throwable;
use Yii;
use yii\log\Logger;
use yii\mutex\FileMutex;

/**
 * Class TicketMessageService
 * @package app\modules\tickets\services
 */
class TicketMessageService extends BaseService
{
    const ACCAUNT_PLANFIX_ID = 1;

    const INTERVAL_UPDATE_FROM_PLANFIX = 604800; // 1 week

    /** @var SyncCabinetTicketRepositoryInterface */
    private $syncCabinetTickets;

    /** @var ExternalCabinetMessagesRepositoryInterface */
    private $externalCabinetMessages;

    /** @var SyncCabinetMessageRepositoryInterface */
    private $syncCabinetMessages;

    /** @var SyncUsersRepositoryInterface */
    private $syncUsers;

    /** @var PlanfixMessagesRepositoryInterface */
    private $planfixMessages;

    /** @var  @var LockService */
    private $lockService;

    public function __construct(
        ExternalCabinetMessagesRepositoryInterface $externalCabinetMessagesRepository,
        SyncCabinetMessageRepositoryInterface $syncCabinetMessageRepository,
        PlanfixMessagesRepositoryInterface $planfixMessagesRepository,
        SyncCabinetTicketRepositoryInterface $syncCabinetTicketRepository,
        SyncUsersRepositoryInterface $syncUsersRepository
    )
    {
        $this->externalCabinetMessages = $externalCabinetMessagesRepository;
        $this->syncCabinetMessages = $syncCabinetMessageRepository;
        $this->planfixMessages = $planfixMessagesRepository;
        $this->syncCabinetTickets = $syncCabinetTicketRepository;
        $this->syncUsers = $syncUsersRepository;
        $this->lockService = new LockService(LockService::LOCK_MESSAGE);

        parent::__construct();
    }

    /**
     * @param int $messageId
     * @throws ServiceException
     */
    public function addMessageToSyncAndPlanfix(int $messageId)
    {
        if ($this->isMessageSynced($messageId)) {
            return;
        }

        if ($this->lockService->lock(LockService::DEFAULT_TIMEOUT_SECONDS)) {

            $externalMessage = $this->externalCabinetMessages->getById($messageId);
            $syncCabinetTicket = $this->syncCabinetTickets->getByTicketId($externalMessage->ticketId);
            $syncUser = $this->syncUsers->getByCabinetUserId($externalMessage->accountId);

            try {
                $this->transactionStart();

                $externalPlanfixTaskMessage = new ExternalPlanfixTaskMessage([
                    'description' => $externalMessage->message,
                    'ownerId' => $syncUser->planfix_userid,
                    'dateTime' => $externalMessage->created,
                    'taskId' => $syncCabinetTicket->planfix_id,
                    'notifiedListUserIds' => [$syncUser->planfix_userid],
                ]);
                $planfixMessageId = $this->planfixMessages->save($externalPlanfixTaskMessage);
                $planfixMessage = $this->planfixMessages->getById($planfixMessageId);

                $syncCabinetTicketMessage = new SyncCabinetTicketMessage();

                $syncCabinetTicketMessage->ticket_message_id = $externalMessage->id;
                $syncCabinetTicketMessage->ticket_message_created = $externalMessage->created;
                $syncCabinetTicketMessage->ticket_message_modified = $externalMessage->modified;
                $syncCabinetTicketMessage->planfix_message_id = $planfixMessage->id;
                $syncCabinetTicketMessage->hash = $planfixMessage->getHash();

                $this->syncCabinetMessages->save($syncCabinetTicketMessage);

                $this->transactionCommit();
            } catch (Throwable $exception) {
                $this->transactionRollback();
                Yii::error($exception);

                throw new ServiceException('OnAddNewMessageToTicket has error');
            }

            $this->lockService->unlock();
        }
    }

    /**
     * @param int $messageId
     * @throws ServiceException
     */
    public function updateMessageToSyncAndPlanfix(int $messageId)
    {
        if ($this->lockService->lock(LockService::DEFAULT_TIMEOUT_SECONDS)) {

            $syncCabinetTicketMessage = $this->syncCabinetMessages->findPlanfixCommentIdByTicketMessageId($messageId);
            $externalMessage = $this->externalCabinetMessages->getById($messageId);

            try {
                $this->transactionStart();

                $externalPlanfixTaskMessage = new ExternalPlanfixTaskMessage([
                    'id' => $syncCabinetTicketMessage->planfix_message_id,
                    'description' => $externalMessage->message,
                ]);
                $planfixMessageId = $this->planfixMessages->update($externalPlanfixTaskMessage);
                $planfixMessage = $this->planfixMessages->getById($planfixMessageId);

                $syncCabinetTicketMessage->ticket_message_modified = $externalMessage->modified;
                $syncCabinetTicketMessage->planfix_message_id = $planfixMessage->id;
                $syncCabinetTicketMessage->hash = $planfixMessage->getHash();

                $this->syncCabinetMessages->save($syncCabinetTicketMessage);

                $this->transactionCommit();
            } catch (Throwable $exception) {
                $this->transactionRollback();
                Yii::error($exception);
                \Yii::getLogger()->log($exception->getMessage(), Logger::LEVEL_ERROR);

                throw new ServiceException('OnUpdateMessageInTicket has error');
            }

            $this->lockService->unlock();
        }
    }

    /**
     * @param string $messageText
     * @param int $ticketId
     * @throws ServiceException
     */
    public function addHiddenMessageToTicket(string $messageText, int $ticketId)
    {
        $syncCabinetTicket = $this->syncCabinetTickets->getByTicketId($ticketId);

        try {
            $externalPlanfixTaskMessage = new ExternalPlanfixTaskMessage([
                'description' => $messageText,
                'ownerId' => env('USER_OWNER_HIDDEN_MESSAGE'),
                'dateTime' => date('Y-m-d H:i:s'),
                'taskId' => $syncCabinetTicket->planfix_id,
                'notifiedListUserIds' => [env('USER_OWNER_HIDDEN_MESSAGE')],
            ]);

            $this->planfixMessages->save($externalPlanfixTaskMessage);
        } catch (Throwable $exception) {
            Yii::error($exception);

            throw new ServiceException('OnAddHiddenMessageToTicket has error');
        }
    }

    /**
     * @param int $messageId
     * @return bool
     */
    private function isMessageSynced(int $messageId): bool
    {
        $ticketMessages = $this->syncCabinetMessages->findByTicketMessageIds([$messageId]);

        return count($ticketMessages) > 0;
    }
}
