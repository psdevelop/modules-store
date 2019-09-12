<?php

namespace app\models\links\offerTask;

use app\components\PlanfixWebService;
use app\exceptions\LinkException;
use app\models\cabinet\CabinetOfferToDomains;
use app\models\links\LinkBase;
use app\models\planfix\PlanfixOfferTask;
use app\models\sync\SyncPlanfixOffersTasks;
use yii\web\Response;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class Planfix extends LinkBase
{
    public $offerId;
    public $domain;
    public $platform;
    public $status;

    public function getHelpView()
    {
        return "partials/link-create-offer-domain-task_help";
    }

    protected $availableTargets = [
        self::TARGET_PLANFIX
    ];

    public function attributeLabels()
    {
        return [
            'offerId' => 'ID Оффера',
            'domain' => 'Трекинговый домен',
            'platform' => 'Платформа (' . implode(' / ', $this->availablePlatforms) . ')',
            'status' => 'Статус',
        ];
    }

    public function rules()
    {
        return array_merge([
            [['status'], 'safe'],
            [['offerId', 'domain', 'platform'], 'required', 'message' => 'Не задан необходимый параметр "{attribute}"'],
            ['platform', 'in', 'range' => array_keys($this->platformSynonyms), 'message' => 'Недопустимое значение "{attribute}"'],
        ], parent::rules());
    }

    /**
     * @return Response|string
     */
    public function openHandler()
    {
        $planfixUrl = $this->trackingDomainTaskPlanfix();
        if ($this->module == LinkBase::MODULE_API) {
            return $planfixUrl;
        }
        return \Yii::$app->response->redirect($planfixUrl);
    }

    /**
     * @return Response|string
     */
    public function updateAndOpenHandler()
    {
        $planfixUrl = $this->trackingDomainTaskPlanfix(true);
        if ($this->module == LinkBase::MODULE_API) {
            return $planfixUrl;
        }
        return \Yii::$app->response->redirect($planfixUrl);
    }

    /**
     * @param bool $forceUpdate
     * @return string
     * @throws LinkException
     */
    public function trackingDomainTaskPlanfix($forceUpdate = false)
    {
        $platformId = $this->getPlatformIdField();
        if (!$offerToDomain = CabinetOfferToDomains::getByOfferDomain($this->offerId, $this->domain, $this->platform)) {
            throw new LinkException(
                "Не найдена связка Оффер:$this->offerId / Домен: $this->domain",
                LinkException::ERROR_HEADER_NOT_FOUND,
                [],
                null,
                404
            );
        }

        $offerToDomain->setBaseIds($this->getPlatform());

        /**
         * @var SyncPlanfixOffersTasks $syncObject
         */
        $syncObject = SyncPlanfixOffersTasks::find()
            ->where(['=', $platformId, $offerToDomain->id])
            ->one();

        if (!$syncObject) {
            $planfixTask = new PlanfixOfferTask();
            $syncObject = $planfixTask->addByOfferDomain($offerToDomain);
            $forceUpdate = false;
        }

        if ($forceUpdate) {
            $planfixTask = PlanfixOfferTask::instance();
            $planfixTask->updateOfferDomain($syncObject);
        }

        /**
         * @var $planfixWs PlanfixWebService
         */
        $planfixWs = \Yii::$app->planfixWs;
        if (!$planfixAccount = $planfixWs->getConfig('account') ?? null) {
            throw new LinkException("Некорректный аккаунт Planfix", LinkException::ERROR_HEADER_INVALID_QUERY);
        };

        return sprintf($this->planfixTaskUrlMask, $planfixAccount, $syncObject->planfix_general_id);
    }

}
