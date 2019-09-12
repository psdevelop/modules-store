<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 05.09.17
 * Time: 11:39
 */

namespace app\models\planfix;


use app\components\helpers\LogHelper;
use app\models\cabinet\CabinetAdvertiser;
use app\models\cabinet\CabinetEmployee;
use app\models\cabinet\CabinetOffer;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixOffers;

class PlanfixProjectOffer extends PlanfixProject
{
    /**
     * @var array мап
     */
    public $projectOfferMap;

    /**
     * Получить маппинг или статус Планфикс по статусу оффера в кабинете
     * @param null $cabinetStatus
     * @return array|mixed|null
     */
    public function getStatusesCabinetToPlanfix($cabinetStatus = null)
    {
        $map = [
            CabinetOffer::STATUS_ACTIVE => PlanfixProject::STATUS_ACTIVE,
            CabinetOffer::STATUS_PENDING => PlanfixProject::STATUS_DRAFT,
            CabinetOffer::STATUS_PAUSED => PlanfixProject::STATUS_DRAFT,
            CabinetOffer::STATUS_DELETED => PlanfixProject::STATUS_COMPLETED,
        ];

        return $cabinetStatus ? ($map[$cabinetStatus] ?? null) : $map;
    }

    /**
     * @param $object CabinetOffer
     * @param $pattern
     * @return string
     */
    public function getProjectOfferTitle($object, $pattern)
    {
        $platform = $object->base == 'leads' ? 'Л' : ($object->base == 'trade' ? 'Т' : '');
        $id = $object->id;
        $name = $object->name;
        return sprintf($pattern, $platform, $id, $name);
    }

    /**
     * @param $object CabinetOffer
     * @param $params
     * @return string
     */
    public function getProjectStatus($object, $params)
    {
        return $this->getStatusesCabinetToPlanfix($object->status);
    }

    /**
     * @param $object CabinetOffer
     * @param $params
     * @return array|null
     */
    public function getOwner($object, $params)
    {
        /**
         * @var $employee CabinetEmployee
         */
        if (!$employee = $object->employee) {
            return null;
        }

        $planfixUsers = PlanfixBase::instance()->getAllPlanfixUsers();

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
            'id' => $planfixUser['id']
        ];
    }

    /**
     * @param $object CabinetOffer
     * @param $params
     * @return array|null
     */
    public function getClient($object, $params)
    {
        if (!$advertiser = $object->advertiser) {
            LogHelper::error("Не найден рекламодатель оффера");
            return null;
        }

        if(!$syncAdvertiser = $advertiser->getPlanfix()){
            return null;
        }

        return [
            'id' => $syncAdvertiser->planfix_id ?? null
        ];
    }

}