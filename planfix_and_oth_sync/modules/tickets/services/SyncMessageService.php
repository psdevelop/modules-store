<?php

namespace app\modules\tickets\services;

use app\modules\tickets\dto\AddNewMessageToTaskDTO;
use app\modules\tickets\dto\AddNewMessageToTicketDTO;
use app\modules\tickets\enum\TicketAccountTypeEnum;
use app\modules\tickets\jobs\OnAddNewMessageToTaskJob;
use app\modules\tickets\jobs\OnAddNewTicketMessageJob;
use app\modules\tickets\jobs\OnChangeMessageTaskJob;
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

class SyncMessageService extends BaseService
{
    const ACCAUNT_PLANFIX_ID = 1;

    const INTERVAL_UPDATE_FROM_PLANFIX = 604800; // 1 week

    /** @var SyncCabinetTicketRepositoryInterface */
    private $syncCabinetTickets;

    /** @var ExternalCabinetMessagesRepositoryInterface */
    private $externalCabinetMessages;

    /** @var SyncCabinetMessageRepositoryInterface */
    private $syncCabinetMessages;

    /** @var PlanfixMessagesRepositoryInterface */
    private $planfixMessages;

    /** @var FilterTicketMessagesService */
    private $filterTicketMessagesService;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        ExternalCabinetMessagesRepositoryInterface $externalCabinetMessagesRepository,
        SyncCabinetMessageRepositoryInterface $syncCabinetMessageRepository,
        PlanfixMessagesRepositoryInterface $planfixMessagesRepository,
        SyncCabinetTicketRepositoryInterface $syncCabinetTicketRepository,
        FilterTicketMessagesService $filterTicketMessagesService,
        JobSyncService $jobSyncService
    ) {
        $this->externalCabinetMessages = $externalCabinetMessagesRepository;
        $this->syncCabinetMessages = $syncCabinetMessageRepository;
        $this->planfixMessages = $planfixMessagesRepository;
        $this->syncCabinetTickets = $syncCabinetTicketRepository;
        $this->filterTicketMessagesService = $filterTicketMessagesService;
        $this->jobSyncService = $jobSyncService;

        parent::__construct();
    }

    public function syncLeadsToPlanfix()
    {
        try {
            $dateFrom = $this->syncCabinetMessages->getDateLastSyncMessage();
            $modifiedExternalMessages = $this->externalCabinetMessages->findFromDate($dateFrom);

            if (count($modifiedExternalMessages) === 0) {
                return;
            }

            $externalNewMessages = $this->filterTicketMessagesService->getNewExternalMessages($modifiedExternalMessages);

            foreach ($externalNewMessages as $externalNewMessage) {
                $addNewMessageToTicketDTO = new AddNewMessageToTicketDTO();

                $addNewMessageToTicketDTO->type = (Yii::$container->get(EnvironmentService::class))->getProjectEnvironment();
                $addNewMessageToTicketDTO->messageId = $externalNewMessage->id;

                $this->jobSyncService->addJob(
                    OnAddNewTicketMessageJob::class,
                    ['addNewMessageToTicketDTO' => $addNewMessageToTicketDTO]
                );
            }
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw new ServiceException('sync ticket messages -> planfix has error');
        }
    }

    /**
     * @throws ServiceException
     */
    public function syncPlanfixToLeads()
    {
        try {
            $dateLastSync = $this->syncCabinetTickets->getDateLastSyncTicket();
            $dateFrom =  date('Y-m-d H:i:s', $dateLastSync->getTimestamp() - self::INTERVAL_UPDATE_FROM_PLANFIX);
            $syncCabinetTickets = $this->syncCabinetTickets->findModifiedAndCreatedFromDate($dateFrom);

            if (! count($syncCabinetTickets)) {
                return;
            }

            $taskMessages = [];
            foreach ($syncCabinetTickets as $syncCabinetTicket) {
                $messages = $this->planfixMessages->getAllByTaskIdOrderAsc($syncCabinetTicket->planfix_id);
                $taskMessages = array_merge($taskMessages, $messages);
            }

            if (! count($taskMessages)) {
                return;
            }

            $taskMessagesNewSign = $this->filterTicketMessagesService->getMessagesByNewSign($taskMessages);
            $taskMessagesForSync = $this->filterTicketMessagesService->getMessagesByNotificationList($taskMessagesNewSign);

            foreach ($taskMessagesForSync as $taskMessage) {
                $addNewMessageToTaskDTO = new AddNewMessageToTaskDTO();

                $addNewMessageToTaskDTO->type = (Yii::$container->get(EnvironmentService::class))->getProjectEnvironment();
                $addNewMessageToTaskDTO->messageId = $taskMessage->id;

                $this->jobSyncService->addJob(
                    OnAddNewMessageToTaskJob::class,
                    ['addNewMessageToTaskDTO' => $addNewMessageToTaskDTO]
                );
            }

            $taskChangedMessages = $this->filterTicketMessagesService->getChangedMessages($taskMessages);

            foreach ($taskChangedMessages as $taskMessage) {
                $addNewMessageToTaskDTO = new AddNewMessageToTaskDTO();

                $addNewMessageToTaskDTO->type = (Yii::$container->get(EnvironmentService::class))->getProjectEnvironment();
                $addNewMessageToTaskDTO->messageId = $taskMessage->id;

                $this->jobSyncService->addJob(
                    OnChangeMessageTaskJob::class,
                    ['addNewMessageToTaskDTO' => $addNewMessageToTaskDTO]
                );
            }
        } catch (Throwable $exception) {
            Yii::error($exception);

            throw new ServiceException('sync task planfix messages -> leads messages has error');
        }
    }
}
