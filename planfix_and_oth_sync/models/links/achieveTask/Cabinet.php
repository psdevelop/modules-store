<?php

namespace app\models\links\achieveTask;

use app\exceptions\LinkException;
use app\models\cabinet\CabinetAchievements;
use app\models\links\LinkAchieveTask;
use app\models\planfix\PlanfixOfferTask;
use app\models\planfix\PlanfixTask;
use app\models\sync\SyncPlanfixAchievementsTasks;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class Cabinet extends LinkAchieveTask
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
            'comment' => 'Комментарий',
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge([
            [['comment'], 'safe'],
            [['taskId'], 'required', 'message' => 'Не задан необходимый параметр "{attribute}"'],
            ['status', 'in', 'range' => PlanfixOfferTask::instance()->getAvailableStatuses(), 'message' => 'Недопустимое значение "{attribute}"'],
            ['target', 'in', 'range' => $this->availableTargets, 'message' => 'Недопустимое значение "{attribute}"'],
        ], parent::rules());
    }

    /**
     *
     * @return string
     * @throws LinkException
     */
    public function giveHandler()
    {
        /**
         * @var $syncTask SyncPlanfixAchievementsTasks
         */
        $syncTask = SyncPlanfixAchievementsTasks::findOne(['planfix_id' => $this->taskId]);

        if (!$syncTask) {
            echo "Неизвестная задача $this->taskId!";
            die;
        }

        /**
         * @var $achievement CabinetAchievements
         */
        $achievement = $syncTask->getSyncCabinetObject();

        if ($achievement->closed_at != 0) {
            $this->repairStatusTask($syncTask->status_task);
            die;
        }

        $cabinetApi = $this->cabinetApi;
        // Запрос на кабинет-API
        $cabinetApi
            ->run($achievement->base)
            ->achievementReward(
                $achievement->id
            );

        if ($error = $cabinetApi->getError()) {
            echo implode(' | ',array_column($error->errorParams,'message')) ?? 'Неизвестная ошибка';
            die;
        }


        if(!$this->closeTask()){
            echo "Ошибка закрытия задачи!";
            die;
        }
        $syncTask->status_task = PlanfixTask::TASK_STATUS_COMPLETED;
        $syncTask->save();
        echo "Бонус за достижение успешно начислен!";
        die;
    }

    /**
     *
     * @return string
     * @throws LinkException
     */
    public function rejectHandler()
    {
        /**
         * @var $syncTask SyncPlanfixAchievementsTasks
         */
        $syncTask = SyncPlanfixAchievementsTasks::findOne(['planfix_id' => $this->taskId]);

        if (!$syncTask) {
            echo "Неизвестная задача $this->taskId!";
            die;
        }

        /**
         * @var $achievement CabinetAchievements
         */
        $achievement = $syncTask->getSyncCabinetObject();

        if ($achievement->closed_at != 0) {
            $this->repairStatusTask($syncTask->status_task);
            die;
        }

        $cabinetApi = $this->cabinetApi;
        // Запрос на кабинет-API
        $cabinetApi
            ->run($achievement->base)
            ->achievementReject(
                $achievement->id
            );

        if ($error = $cabinetApi->getError()) {
            echo "API: " .implode(' | ',array_column($error->errorParams,'message')) ?? 'Неизвестная ошибка';
            die;
        }
        $syncTask->status_task = PlanfixTask::TASK_STATUS_REJECTED;
        $syncTask->save();
        echo "Бонус за достижение отклонен!";
        die;
    }

    protected function repairStatusTask($status)
    {
        if($status == PlanfixTask::TASK_STATUS_REJECTED){
            echo "Достижение уже отклонено! Изменить статус невозможно";
            $this->rejectTask();
        } elseif ($status == PlanfixTask::TASK_STATUS_COMPLETED){
            echo "Достижение уже обработано! Изменить статус невозможно";
            $this->closeTask();
        }
    }

    protected function closeTask()
    {
        $planfixTask = PlanfixTask::instance();
        $planfixTask->id = $this->taskId;
        $planfixTask->status = PlanfixTask::TASK_STATUS_COMPLETED;
        return $planfixTask->update();
    }

    protected function rejectTask()
    {
        $planfixTask = PlanfixTask::instance();
        $planfixTask->id = $this->taskId;
        $planfixTask->status = PlanfixTask::TASK_STATUS_REJECTED;
        return $planfixTask->update();
    }
}
