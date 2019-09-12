<?php

namespace app\modules\tickets\services;

use app\modules\tickets\repositories\AbstractDBExternalAgencyCabinetRepository;
use app\modules\tickets\repositories\ApiPlanfixContactRepository;
use app\modules\tickets\repositories\ApiPlanfixTaskMessageRepository;
use app\modules\tickets\repositories\ApiPlanfixTaskRepository;
use app\modules\tickets\repositories\ARSyncCabinetMessageRepository;
use app\modules\tickets\repositories\ARSyncCabinetTicketRepository;
use app\modules\tickets\repositories\ARSyncUsersRepository;
use app\modules\tickets\repositories\contracts\ExternalCabinetMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\ExternalCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixContactRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixMessagesRepositoryInterface;
use app\modules\tickets\repositories\contracts\PlanfixTaskRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetMessageRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncCabinetTicketRepositoryInterface;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;
use app\modules\tickets\repositories\DBBlackAgencyCabinetRepository;
use app\modules\tickets\repositories\DBBlackExternalCabinetMessagesRepository;
use app\modules\tickets\repositories\DBBlackExternalCabinetTicketRepository;
use app\modules\tickets\repositories\DBLeadsAgencyCabinetRepository;
use app\modules\tickets\repositories\DBLeadsExternalCabinetMessagesRepository;
use app\modules\tickets\repositories\DBLeadsExternalCabinetTicketRepository;
use Yii;
use app\services\Environment as AbstractEnvironment;

class EnvironmentService extends AbstractEnvironment
{
    /** @inheritDoc */
    public function init()
    {
        if ($this->project === AbstractEnvironment::PROJECT_BLACK) {
            $this->setDIBlack();
            return;
        }

        $this->setDILeads();
    }

    private function setDILeads()
    {
        Yii::$container->setSingletons([
            AbstractDBExternalAgencyCabinetRepository::class => DBLeadsAgencyCabinetRepository::class,
            ExternalCabinetTicketRepositoryInterface::class => DBLeadsExternalCabinetTicketRepository::class,
            SyncCabinetTicketRepositoryInterface::class => ARSyncCabinetTicketRepository::class,
            PlanfixTaskRepositoryInterface::class => ApiPlanfixTaskRepository::class,

            ExternalCabinetMessagesRepositoryInterface::class => DBLeadsExternalCabinetMessagesRepository::class,
            SyncCabinetMessageRepositoryInterface::class => ARSyncCabinetMessageRepository::class,
            PlanfixMessagesRepositoryInterface::class => ApiPlanfixTaskMessageRepository::class,
            SyncUsersRepositoryInterface::class => ARSyncUsersRepository::class,

            PlanfixContactRepositoryInterface::class => ApiPlanfixContactRepository::class,
        ]);
    }

    private function setDIBlack()
    {
        Yii::$container->setSingletons([
            AbstractDBExternalAgencyCabinetRepository::class => DBBlackAgencyCabinetRepository::class,
            ExternalCabinetTicketRepositoryInterface::class => DBBlackExternalCabinetTicketRepository::class,
            SyncCabinetTicketRepositoryInterface::class => ARSyncCabinetTicketRepository::class,
            PlanfixTaskRepositoryInterface::class => ApiPlanfixTaskRepository::class,

            ExternalCabinetMessagesRepositoryInterface::class => DBBlackExternalCabinetMessagesRepository::class,
            SyncCabinetMessageRepositoryInterface::class => ARSyncCabinetMessageRepository::class,
            PlanfixMessagesRepositoryInterface::class => ApiPlanfixTaskMessageRepository::class,
            SyncUsersRepositoryInterface::class => ARSyncUsersRepository::class,

            PlanfixContactRepositoryInterface::class => ApiPlanfixContactRepository::class,
        ]);
    }
}