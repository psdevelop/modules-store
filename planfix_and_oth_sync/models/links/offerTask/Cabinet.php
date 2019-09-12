<?php

namespace app\models\links\offerTask;

use app\exceptions\LinkException;
use app\models\links\LinkBase;
use app\models\planfix\PlanfixOfferTask;
use app\models\sync\SyncPlanfixOffersTasks;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class Cabinet extends LinkBase
{
    public $taskId;
    public $domain;
    public $status;
    public $comment;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->cabinetApi = \Yii::$app->cabinetApi;
    }

    public function getHelpView()
    {
        return "partials/link-create-offer-domain-task_help";
    }

    protected $availableTargets = [
        self::TARGET_CABINET,
    ];

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'taskId' => 'ID Задачи',
            'status' => 'Статус',
            'comment' => 'Комментарий',
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge([
            [['status', 'comment'], 'safe'],
            [['taskId'], 'required', 'message' => 'Не задан необходимый параметр "{attribute}"'],
            ['status', 'in', 'range' => PlanfixOfferTask::instance()->getAvailableStatuses(), 'message' => 'Недопустимое значение "{attribute}"'],
            ['target', 'in', 'range' => $this->availableTargets, 'message' => 'Недопустимое значение "{attribute}"'],
        ], parent::rules());
    }

    /**
     * Хэндлер смены статуса по связке Оффер - Трекинговый домен
     * @return string
     * @throws LinkException
     */
    public function changeStatusHandler()
    {
        if ($this->target != LinkBase::TARGET_CABINET) {
            throw new LinkException("Метод недоступен для цели $this->target", LinkException::ERROR_HEADER_ACCESS);
        }

        /**
         * @var $syncTask SyncPlanfixOffersTasks
         */
        if (!$syncTask = SyncPlanfixOffersTasks::findOne(['planfix_id' => $this->taskId])) {
            LinkException::notFound("Задача не найдена!");
        }

        if (!$cabinetObject = $syncTask->getSyncCabinetObject()) {
            LinkException::notFound("Задача не найдена!");
        }

        if (!$cabinetStatus = PlanfixOfferTask::instance()->getRuStatusesToCabinet($this->status)) {
            throw new LinkException("Недопустимый статус $this->status", LinkException::ERROR_HEADER_ACCESS);
        }

        $response = $this->cabinetApi
            ->run($cabinetObject->base)
            ->updateOffersToTrackingDomainStatus(
                (int)$cabinetObject->id,
                (string)$cabinetStatus
            );

        if ($error = $this->cabinetApi->getError()) {
            throw new LinkException("Не удалось обновить статус!", "Ошибка API кабинета", $error);
        }

        return $response['data'] ?? [];
    }

    /**
     * Хэндлер смены статуса по связке Оффер - Трекинговый домен
     * @return string
     * @throws LinkException
     */
    public function changeCommentHandler()
    {
        if ($this->target != LinkBase::TARGET_CABINET) {
            throw new LinkException("Метод недоступен для цели $this->target", LinkException::ERROR_HEADER_ACCESS);
        }

        if (!$this->comment) {
            throw new LinkException("Поле комментарий не может быть пустым!", LinkException::ERROR_HEADER_INVALID_QUERY);
        }
        /**
         * @var $syncTask SyncPlanfixOffersTasks
         */
        if (!$syncTask = SyncPlanfixOffersTasks::findOne(['planfix_id' => $this->taskId])) {
            LinkException::notFound("Задача не найдена!");
        }

        if (!$cabinetObject = $syncTask->getSyncCabinetObject()) {
            LinkException::notFound("Задача не найдена!");
        }

        $response = $this->cabinetApi
            ->run($cabinetObject->base)
            ->updateOffersToTrackingDomainComment(
                (int)$cabinetObject->id,
                (string)$this->comment
            );

        if ($error = $this->cabinetApi->getError()) {
            throw new LinkException("Не удалось обновить комментарий!", "Ошибка API кабинета", $error);
        }

        return $response['data'] ?? [];
    }
}
