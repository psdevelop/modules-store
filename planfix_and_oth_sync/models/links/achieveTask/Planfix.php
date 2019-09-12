<?php

namespace app\models\links\achieveTask;

use app\components\enums\ContactTypesEnum;
use app\exceptions\LinkException;
use app\exceptions\SyncException;
use app\models\cabinet\CabinetAchievements;
use app\models\links\LinkAchieveTask;
use app\models\planfix\PlanfixAchievementTask;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixUsers;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class Planfix extends LinkAchieveTask
{
    public $platform;
    public $status;
    public $achievement_id;
    public $amount;

    public function getHelpView()
    {
        return null;//"partials/link-create-offer-domain-task_help";
    }

    protected $availableTargets = [
        self::TARGET_PLANFIX,
    ];

    /**
     * @var SyncBase[]
     */
    protected $availableTypes = [
        ContactTypesEnum::TYPE_AFFILIATE => SyncPlanfixCompanies::class,
        ContactTypesEnum::TYPE_ADVERTISER => SyncPlanfixCompanies::class,
        ContactTypesEnum::TYPE_MANAGER => SyncPlanfixUsers::class,
    ];

    public function attributeLabels()
    {
        return [
            'platform' => 'Платформа (' . implode(' / ', $this->availablePlatforms) . ')',
            'status' => 'Статус',
            'achievement_id' => 'ID Достижения',
            'amount' => 'Сумма',
        ];
    }

    public function rules()
    {
        return array_merge([
            [['status'], 'safe'],
            [['platform', 'achievement_id', 'amount'], 'required', 'message' => 'Не задан необходимый параметр "{attribute}"'],
            ['type', 'in', 'range' => array_keys($this->availableTypes), 'message' => 'Недопустимое значение "{attribute}"'],
            [['achievement_id'], 'number'],
            ['platform', 'in', 'range' => array_keys($this->platformSynonyms), 'message' => 'Недопустимое значение "{attribute}"'],
        ], parent::rules());
    }

    public function getAchieve($platform)
    {
        CabinetAchievements::setDbByBase($platform);
        $achievement = CabinetAchievements::findOne($this->achievement_id);
        $achievement->setBaseIds($platform);
        return $achievement;
    }

    /**
     * @return string
     * @throws LinkException
     * @throws SyncException
     */
    public function newHandler()
    {
        $platform = $this->getPlatform();
        $achievement = $this->getAchieve($platform);

        if (!$achievement) {
            LinkException::notFound("Достижение не найдено!");
        }

        $task = PlanfixAchievementTask::instance();
        $task->amount = bcadd($this->amount, 0, 2);
        $task->toPlanfix($achievement, $task->taskBonusMap);
        $syncRecord = $task->addByAchievement($achievement);

        $planfixWs = \Yii::$app->planfixWs;
        if (!$planfixAccount = $planfixWs->getConfig('account') ?? null) {
            throw new LinkException("Некорректный аккаунт Planfix", LinkException::ERROR_HEADER_INVALID_QUERY);
        };

        return sprintf($this->planfixTaskUrlMask, $planfixAccount, $syncRecord->planfix_general_id);
    }

}
