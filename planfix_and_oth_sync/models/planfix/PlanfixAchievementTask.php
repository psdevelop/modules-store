<?php

namespace app\models\planfix;


use app\components\enums\AchievementTypesEnum;
use app\components\enums\ContactTypesEnum;
use app\exceptions\LinkException;
use app\models\cabinet\CabinetAchievements;
use app\models\cabinet\CabinetAffiliateBillingPayoutRequest;
use app\models\cabinet\CabinetBase;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixAchievementsTasks;

class PlanfixAchievementTask extends PlanfixTask
{
    public $taskBonusMap;
    public $taskOfferStatusMap;

    public $amount;

    /**
     * @param $cabinetObject CabinetAchievements
     * @param $template
     * @return null|string
     */
    public function getTitle($cabinetObject, $template)
    {
        $contact = $cabinetObject->client;
        $prefix = $contact->getBasePrefix();
        $type = ContactTypesEnum::getClientValue($cabinetObject->account_type);
        $achieveType = AchievementTypesEnum::getClientValue($cabinetObject->achieve_code);
        return sprintf($template, ucfirst($achieveType), $prefix, $contact->getFullName(), $type);
    }

    /**
     * @param $cabinetObject CabinetAchievements
     * @param $template
     * @return string
     */
    public function getDescription($cabinetObject, $template)
    {
        $client = $cabinetObject->client;
        $client->setBaseIds($cabinetObject);

        switch ($cabinetObject->achieve_code) {
            case AchievementTypesEnum::ACHIEVE_INFANT_SCHOOL:
                return \Yii::$app->controller->renderFile(
                    \Yii::$app->getBasePath() . '/views/sync/bonus-task-description__planfix.php',
                    [
                        'client' => $client,
                        'amount' => $this->amount,
                    ]
                );
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * @param $cabinetObject CabinetAchievements
     * @param $params
     * @return array|null
     */
    public function getWorkers($cabinetObject, $params)
    {
        switch ($cabinetObject->account_type) {
            case ContactTypesEnum::TYPE_MANAGER:
                $employee = $cabinetObject->client;
                break;
            case ContactTypesEnum::TYPE_ADVERTISER:
            case ContactTypesEnum::TYPE_AFFILIATE:
                $employee = $cabinetObject->client->employee;
                break;
            default:
                return null;
                break;
        }

        if (!$planfixUsers = PlanfixUser::instance()->planfixUsers) {
            $planfixUsers = PlanfixUser::instance()->getAllPlanfixUsers();
        }

        if (getenv('app_config') == 'development') {
            $emails = [
                'af@leads.su_fake',
                'rbp@leads.su_fake',
                'vp@leads.su',
            ];
            $employee->email = $emails[array_rand($emails)];
        }

        if (!$planfixUser = $planfixUsers[$employee->email] ?? null) {
            return null;
        }

        return [
            'users' => [
                'id' => $planfixUser['id'],
            ],
        ];
    }

    /**
     * @param $cabinetObject CabinetAchievements
     * @param $idType
     * @return null|string
     */
    public function getClient($cabinetObject, $idType)
    {
        if (!$planfixContact = $cabinetObject->client->getPlanfix()) {
            return null;
        }

        return [
            'id' => $planfixContact->{$idType},
        ];
    }

    public function addByAchievement(CabinetAchievements $achievement)
    {
        if (!$syncRecord = SyncPlanfixAchievementsTasks::getByCabinet($achievement, $achievement->base)) {
            if (!$added = $this->add()) {
                throw new LinkException("Не удалось создать задачу!\n" . json_encode($this->error, JSON_UNESCAPED_UNICODE));
            }

            $data = $added['data'][$this->objectName];
            $platformId = $achievement->getBase() . "_id";
            $syncRecord = new SyncPlanfixAchievementsTasks();
            $syncRecord->planfix_id = $data['id'] ?? null;
            $syncRecord->planfix_general_id = $data['general'] ?? null;
            $syncRecord->{$platformId} = $achievement->id;
            $syncRecord->status_sync = SyncBase::STATUS_NONE;
            $syncRecord->status_task = PlanfixTask::TASK_STATUS_WORK;
            $syncRecord->save();
        }
        return $syncRecord;
    }
}
