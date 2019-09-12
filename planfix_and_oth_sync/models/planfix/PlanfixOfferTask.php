<?php

namespace app\models\planfix;


use app\components\helpers\LogHelper;
use app\exceptions\LinkException;
use app\exceptions\SyncException;
use app\models\cabinet\CabinetBase;
use app\models\cabinet\CabinetOfferToDomains;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixOffers;
use app\models\sync\SyncPlanfixOffersTasks;

class PlanfixOfferTask extends PlanfixTask
{
    public $taskOfferMap;
    public $taskOfferStatusMap;

    public function getIdStatusesToCabinetMap()
    {
        return [
            PlanfixTask::TASK_STATUS_NEW => CabinetOfferToDomains::DOMAIN_STATUS_NONE,
            PlanfixTask::TASK_STATUS_WORK => CabinetOfferToDomains::DOMAIN_STATUS_INSTALLING,
            $this->getCustomStatusId('feedback') => CabinetOfferToDomains::DOMAIN_STATUS_INSTALLING,
            $this->getCustomStatusId('testing') => CabinetOfferToDomains::DOMAIN_STATUS_TESTING,
            PlanfixTask::TASK_STATUS_COMPLETED => CabinetOfferToDomains::DOMAIN_STATUS_INSTALLED,
            PlanfixTask::TASK_STATUS_CANCELED => CabinetOfferToDomains::DOMAIN_STATUS_NOT_REQUIRED
        ];
    }

    public function getRuStatusesToCabinetMap()
    {
        return [
            PlanfixTask::TASK_RU_STATUS_NEW => CabinetOfferToDomains::DOMAIN_STATUS_NONE,
            PlanfixTask::TASK_RU_STATUS_WORK => CabinetOfferToDomains::DOMAIN_STATUS_INSTALLING,
            $this->getCustomStatusRuValue('feedback') => CabinetOfferToDomains::DOMAIN_STATUS_INSTALLING,
            $this->getCustomStatusRuValue('testing') => CabinetOfferToDomains::DOMAIN_STATUS_TESTING,
            PlanfixTask::TASK_RU_STATUS_COMPLETED => CabinetOfferToDomains::DOMAIN_STATUS_INSTALLED,
            PlanfixTask::TASK_RU_STATUS_CANCELED => CabinetOfferToDomains::DOMAIN_STATUS_NOT_REQUIRED
        ];
    }

    /**
     * @return array
     */
    public function getAvailableStatuses()
    {
        $planfix = PlanfixBase::instance();
        return [
            PlanfixTask::TASK_RU_STATUS_NEW,
            PlanfixTask::TASK_RU_STATUS_WORK,
            $planfix->getCustomStatusRuValue('feedback'),
            $planfix->getCustomStatusRuValue('testing'),
            PlanfixTask::TASK_RU_STATUS_COMPLETED,
            PlanfixTask::TASK_RU_STATUS_CANCELED
        ];
    }


    /**
     * @param null $planfixStatus
     * @return array|string
     */
    public function getIdStatusesToCabinet($planfixStatus = null)
    {
        $map = $this->getIdStatusesToCabinetMap();
        return $planfixStatus ? ($map[$planfixStatus] ?? null) : $map;
    }

    /**
     * @param null $planfixStatus
     * @return array|string
     */
    public function getRuStatusesToCabinet($planfixStatus = null)
    {
        $map = $this->getRuStatusesToCabinetMap();
        return $planfixStatus ? ($map[$planfixStatus] ?? null) : $map;
    }

    /**
     * @param $offerToDomain CabinetOfferToDomains
     * @param $template
     * @return null|string
     */
    public function getTitle($offerToDomain, $template)
    {
        $domain = $offerToDomain->domain->domain;
        $offerId = $offerToDomain->offer->id;
        $offerName = $offerToDomain->offer->name;
        return sprintf($template, $domain, $offerId, $offerName);
    }

    /**
     * @param $offerToDomain CabinetOfferToDomains
     * @param $template
     * @return string
     */
    public function getDescription($offerToDomain, $template)
    {
        $offer = $offerToDomain->offer;
        $domain = $offerToDomain->domain;
        return \Yii::$app->controller->renderFile(
            \Yii::$app->getBasePath() . '/views/sync/offer-task-description__planfix.php',
            [
                'domain' => $domain,
                'offer' => $offer,
                'offerPrefix' => $offer->getBasePrefix(),
                'offerUrl' => PlanfixCustomData::instance()->getCabinetOfferUrl($offer,'manager')
            ]
        );
    }

    /**
     * @param $offerToDomain CabinetOfferToDomains
     * @param $params
     * @return array|null
     */
    public function getWorkers($offerToDomain, $params)
    {
        if (!$employee = $offerToDomain->offer->employee) {
            return null;
        }

        if (!$planfixUsers = PlanfixUser::instance()->planfixUsers) {
            $planfixUsers = PlanfixUser::instance()->getAllPlanfixUsers();
        }

        if (getenv('app_config') == 'development') {
            $emails = [
                'af@leads.su_fake',
                'rbp@leads.su_fake',
                'vp@leads.su'
            ];
            $employee->email = $emails[array_rand($emails)];
        }

        if (!$planfixUser = $planfixUsers[$employee->email] ?? null) {
            return null;
        }

        return [
            'users' => [
                'id' => $planfixUser['id']
            ]
        ];
    }

    /**
     * @param $offerToDomain CabinetOfferToDomains
     * @param $params
     * @return null|string
     */
    public function getClient($offerToDomain, $params)
    {
        if (!$offer = $offerToDomain->offer) {
            return null;
        }

        if (!$advertiser = $offerToDomain->offer->advertiser) {
            return null;
        }

        if (!$planfixAdvertiser = $advertiser->getPlanfix()) {
            return null;
        }

        return [
            'id' => $planfixAdvertiser->planfix_id
        ];
    }

    /**
     * @param $offerToDomain CabinetOfferToDomains
     * @param $params
     * @return null|string
     */
    public function getStatus($offerToDomain, $params)
    {
        return $offerToDomain->getPlanfixIdStatus();
    }

    /**
     * @param $offerToDomain CabinetOfferToDomains
     * @param $params
     * @return null
     */
    public function getProject($offerToDomain, $params)
    {
        if (!$offer = $offerToDomain->offer) {
            LogHelper::error("Не найден оффер для связки оффер-домен");
            return null;
        }

        if (!$planfixProject = $offer->getPlanfix()) {
            // Создание нового проекта и его "Подхват"
            $syncOffer = new SyncPlanfixOffers();
            $syncOffer->leads_id = $offer->leads_id ?? null;
            $syncOffer->trade_id = $offer->trade_id ?? null;
            $syncOffer->status_sync = SyncBase::STATUS_ADD;
            $syncOffer->save();
            $newPlanfixProject = new PlanfixProjectOffer();
            $newPlanfixProject->addOffers(SyncPlanfixOffers::toAdd());
            $planfixProject = $offer->getPlanfix();
        }

        if (!$planfixId = $planfixProject->planfix_id) {
            return null;
        }

        return [
            'id' => $planfixId
        ];
    }

    /**
     * @param $offerToDomain CabinetOfferToDomains
     * @return null
     * @throws SyncException
     */
    public function addByOfferDomain($offerToDomain)
    {
        $platform = $offerToDomain->getBase();
        $platformId = $platform . "_id";
        try {
            CabinetBase::setDb('db' . ($platform != 'trade' ? null : 'Trade') . 'Leads');
            CabinetBase::getDb();
        } catch (\Exception $exception) {
            LinkException::systemError($exception->getMessage());
        }
        $this->toPlanfix($offerToDomain, $this->taskOfferMap);
        if (!$added = $this->add()) {
            throw new SyncException("Не удалось создать задачу!");
        }
        $syncObject = new SyncPlanfixOffersTasks();
        $data = $added['data'][$this->objectName];

        $syncObject->{$platformId} = $offerToDomain->{$platformId};
        $this->id = $syncObject->planfix_id = (int)$data['id'] ?? null;
        $syncObject->planfix_general_id = (int)$syncObject->planfix_task_id = $data['general'] ?? null;
        $syncObject->status_sync = SyncBase::STATUS_NONE;
        if (!$syncObject->planfix_id || !$syncObject->planfix_general_id) {
            try {
                $this->delete();
            } catch (\Exception $exception) {
                $this->delete();
                throw new SyncException("Не удалось создать задачу!");
            }
        }

        try {
            $syncObject->save();
        } catch (\Exception $exception) {
            $this->delete();
            throw new SyncException("Не удалось создать задачу!");
        }

        return $syncObject;
    }

    /**
     * @param $syncRecord SyncPlanfixOffersTasks
     * @throws SyncException
     * @return boolean
     */
    public function updateOfferDomain($syncRecord)
    {
        $offerToDomain = $syncRecord->getSyncCabinetObject();
        $this->toPlanfix($offerToDomain, $this->taskOfferStatusMap);
        $this->id = $syncRecord->planfix_id;
        if ($updated = $this->update()) {
            $syncRecord->status_sync = SyncBase::STATUS_NONE;
            $syncRecord->save();
        }
        return true;
    }
}
