<?php

namespace app\modules\contacts\services;

use app\models\cabinet\ContactsPfSyncJob;
use app\models\planfix\PlanfixBase;
use app\models\sync\SyncBase;
use app\modules\contacts\contracts\repositories\IContactsSync;
use app\exceptions\SyncException;

/**
 * Class ContactsSync
 * Класс сервиса синхронизации контактов из
 * текущего проекта в Планфикс
 * @package app\modules\contacts\services
 */
class ContactsSync
{
    /** @var IContactsSync */
    private $repository;

    public function __construct(IContactsSync $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Отправляет в Планфикс созданные в кабинете
     * контакты
     * @throws SyncException
     */
    public function syncToPlanfix()
    {
        /** @var ContactsPfSyncJob[] $jobsList */
        $jobsList = $this->repository->getJobs();

        if (!$jobsList) {
            return;
        }

        $planfixBase = PlanfixBase::instance();
        $planfixBase->getAllPlanfixUsers();

        foreach ($jobsList as $job) {
            /** @var ContactsPfSyncJob $job */

            $contact = $job->getContact();

            if (!$contact) {
                $job->setStatus(ContactsPfSyncJob::SYNC_STATUS_ERROR);
                continue;
            }

            $isBlack = $this->repository->isBlackProject();
            $contact->base = $isBlack ? 'trade' : 'leads';
            $idFieldName = $isBlack ? 'trade_id' : 'leads_id';
            $contact->$idFieldName = $job->account_id;

            /** @var SyncBase $syncRecord */
            $syncRecord = $this->repository
                ->getSyncObject($job);

            $type = $this->repository
                ->getJobContactType($job);

            $result = $planfixBase->addContactToPlanfix(
                $syncRecord,
                $contact,
                $type
            );

            $status = $result
                ? ContactsPfSyncJob::SYNC_STATUS_COMPLETED
                : ContactsPfSyncJob::SYNC_STATUS_ERROR;
            $job->setStatus($status);
        }
    }

}