<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 13.04.17
 * Time: 9:12
 */

namespace app\models\planfix;


use app\components\enums\ContactTypesEnum;
use app\models\cabinet\CabinetBase;
use app\models\cabinet\CabinetChat;
use app\models\cabinet\CabinetCompany;
use app\models\cabinet\CabinetEmployee;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixUnknownUsers;

class PlanfixTask extends PlanfixBase
{
    /* Системные статусы Planfix (id) */
    // Новая
    const TASK_STATUS_NEW = 1;
    // В работе
    const TASK_STATUS_WORK = 2;
    // Завершенная
    const TASK_STATUS_COMPLETED = 3;
    // Отклоненная
    const TASK_STATUS_REJECTED = 5;
    // Отмененная
    const TASK_STATUS_CANCELED = 7;
    // Выполненная
    const TASK_STATUS_DONE = 6;

    /* Системные статусы Planfix (ru) */
    // Новая
    const TASK_RU_STATUS_NEW = 'Новая';
    // В работе
    const TASK_RU_STATUS_WORK = 'В работе';
    // Завершенная
    const TASK_RU_STATUS_COMPLETED = 'Завершенная';
    // Отклоненная
    const TASK_RU_STATUS_REJECTED = 'Отклоненная';
    // Отмененная
    const TASK_RU_STATUS_CANCELED = 'Отмененная';
    // Выполненная
    const TASK_RU_STATUS_DONE = 'Выполненная';

    public $objectName = 'task';
    public $objectKey = 'id';

    public $taskChatMap;

    public $template;
    public $general;
    public $title;
    public $description;
    public $importance;
    public $status;
    public $statusSet;
    public $checkResult;
    public $owner;
    public $parent;
    public $project;
    public $client;
    public $beginDateTime;
    public $startDateIsSet;
    public $startDate;
    public $startTimeIsSet;
    public $startTime;
    public $endDateIsSet;
    public $endDate;
    public $endTimeIsSet;
    public $endTime;
    public $workers;
    public $members;
    public $auditors;
    public $customData;

    public $fields = [
        'id',
        'general',
        'template',
        'title',
        'description',
        'importance',
        'status',
        'statusSet',
        'checkResult',
        'owner',
        'parent',
        'project',
        'client',
        'beginDateTime',
        'startDateIsSet',
        'startDate',
        'startTimeIsSet',
        'startTime',
        'endDateIsSet',
        'endDate',
        'endTimeIsSet',
        'endTime',
        'workers',
        'members',
        'auditors',
        'customData',
    ];

    public $accountRuTypes = [
        ContactTypesEnum::TYPE_AFFILIATE => 'Вебмастер',
        ContactTypesEnum::TYPE_ADVERTISER => 'Рекламодатель'
    ];

    /**
     * Системные статусы Planfix (ru) => Системные статусы Planfix (id)
     * @param null $ruStatus
     * @return array|mixed|null
     */
    public function getStatusIdByRu($ruStatus = null)
    {
        $map = [
            self::TASK_RU_STATUS_NEW => self::TASK_STATUS_NEW,
            self::TASK_RU_STATUS_WORK => self::TASK_STATUS_WORK,
            self::TASK_RU_STATUS_COMPLETED => self::TASK_STATUS_COMPLETED,
            self::TASK_RU_STATUS_REJECTED => self::TASK_STATUS_REJECTED,
            self::TASK_RU_STATUS_CANCELED => self::TASK_STATUS_CANCELED,
            self::TASK_RU_STATUS_DONE => self::TASK_STATUS_DONE,
            $this->getCustomStatusId('feedback') => $this->getCustomStatusId('feedback'),
            $this->getCustomStatusId('testing') => $this->getCustomStatusId('testing'),
        ];

        return $ruStatus ? ($map[$ruStatus] ?? null) : $map;
    }

    /**
     * @param $chat CabinetChat
     * @param $params
     * @return array|null
     */
    public function getOwner($chat, $params)
    {
        if (!$cabinetClient = $chat->client) {
            if (!$syncClient = SyncPlanfixUnknownUsers::find()
                ->where(['email' => $chat->extractEmail()])
                ->orWhere(['name' => $chat->extractName()])
                ->one()
            ) {
                return null;
            }
        } else {
            if (!$chat->base) {
                return null;
            }
            $accountType = $chat->getRealChatAccountType();
            if (!$syncClient = SyncPlanfixCompanies::findOne([$chat->base . '_id' => $cabinetClient->id, 'type' => $accountType])) {
                return null;
            }
        }

        if (!$planfixContactId = $syncClient->planfix_id) {
            return null;
        }

        if (!$planfixContactUserId = $syncClient->planfix_userid) {
            return null;
        }

        return [
            'id' => $planfixContactUserId
        ];
    }

    /**
     * CabinetChat
     * @param $chat CabinetChat
     * @param $params
     * @return array|bool
     */
    public function getChatClient($chat, $params)
    {
        // Проверяем его источник (leads / trade)
        if (!$base = $chat->base) {
            return null;
        }
        // Если у чата нет клиента...
        if (!$cabinetClient = $chat->client) {
            // Пытаемся найти соотвтетсвующую запись в таблице неопознанных пользователей...
            $syncClient = null;
            // Если email заполнен
            if ($chat->extractEmail() !== '') {
                $syncClient = SyncPlanfixUnknownUsers::find()
                    ->where(['email' => $chat->extractEmail()])
                    ->one();
            } else {
                // Если же email пустой, по базе чата - берем статику
                if (!isset($this->phantoms[$base])) {
                    return null;
                }
                return [
                    'id' => $this->phantoms[$base]
                ];

            }
            // Если записи нет - то null
            if (!$syncClient) {
                return null;
            }

        } else {
            // Если же клиент есть...
            $accountType = $chat->getRealChatAccountType();

            // Проверяем наличие в таблице синхронизации
            if (!$syncClient = SyncPlanfixCompanies::findOne([$chat->base . '_id' => $cabinetClient->id, 'type' => $accountType])) {
                return null;
            }
        }

        // Из таблицы синхронизации - берем ИД
        if (!$planfixContactId = $syncClient->planfix_id) {
            return null;
        }

        // Оформляем и возвращаем массив для объекта Планфикс
        return [
            'id' => $planfixContactId
        ];
    }

    /**
     * @param $chat CabinetChat
     * @param $format
     * @return false|string
     */
    public function getCreated($chat, $format)
    {
        return date($format, strtotime($chat->created));
    }

    /**
     * @param $chat CabinetChat
     * @param $format
     * @return false|string
     */
    public function getStartDate($chat, $format)
    {
        return date($format, strtotime($chat->start_date));
    }

    /**
     * @param $chat CabinetChat
     * @param $format
     * @return false|string
     */
    public function getEndDate($chat, $format)
    {
        return date($format, strtotime($chat->end_date));
    }

    /**
     * @param $chat CabinetChat
     * @param $params
     * @return bool
     */
    public function getStartDateIsSet($chat, $params)
    {
        return (bool)$chat->start_date;
    }

    /**
     * @param $chat CabinetChat
     * @param $params
     * @return bool
     */
    public function getEndDateIsSet($chat, $params)
    {
        return (bool)$chat->end_date;
    }

    /**
     * @param $chat CabinetChat
     * @param $format
     * @return array
     */
    public function getMembers($chat, $format)
    {
        $users = PlanfixBase::instance()->planfixUsers;
        /**
         * @var $chatUser CabinetEmployee
         */

        if (!isset($chat->agent)) {
            return null;
        }

        if (!isset($chat->agent->employee)) {
            return null;
        }

        $chatUser = $chat->agent->employee;
        $chatUserEmail = $chatUser->email;

        $planfixUser = isset($users[$chatUserEmail]) ? $users[$chatUserEmail] : null;

        $chat->planfixChatWorkers;
        $planfixUserId = isset($planfixUser['id']) ? $planfixUser['id'] : null;

        if ($planfixUserId) {
            if ($format == 'array') {
                return [
                    'users' => [
                        'id' => $planfixUserId
                    ]
                ];
            }
            return ['id' => $planfixUserId];

        }
        return null;
    }

    /**
     * @param $chat CabinetChat
     * @param $format
     * @return string
     */
    public function getChatTitle($chat, $format)
    {
        if (!$client = $chat->client) {
            return 'Jivochat с Гостем ' . ($chat->extractEmail() ? $chat->extractEmail() : '');
        }

        $title =
            'Jivochat '
            . $client->getBasePrefix()
            . ', '
            . ($client->company ? $client->company : '(без названия)');

        return $title . ' ' . ($chat->chat_type == 'offline' ? '(оффлайн)' : '(онлайн)') . ' - ' . (isset($this->accountRuTypes[$chat->account_type]) ? $this->accountRuTypes[$chat->account_type] : '');
    }

    /**
     * Проекты для чатиков
     * @param $chat
     * @param $params
     * @return null
     */
    public function getChatProject($chat, $params)
    {
        if (!$base = $chat->base) {
            return null;
        }

        return [
            'id' => $this->config['projects']['jivoChatProjects'][$base] ?? null
        ];
    }

    /**
     * Таски спринта по ID спринта
     * @param $sprintId
     * @return array
     */
    public static function getScrumTasks($sprintId)
    {
        $pointsField = PlanfixBase::instance()->customFields['scrumPoints'] ?? null;
        $sprintField = PlanfixBase::instance()->customFields['sprint'] ?? null;
        $typeField = PlanfixBase::instance()->customFields['scrumType'] ?? null;
        $scrumPlanner = PlanfixBase::instance()->filters['scrumTasks'];

        return PlanfixBase::catchCustomValues(
            PlanfixTask::find([
                'target' => $scrumPlanner,
            ]),
            [
                $pointsField => 'points',
                $sprintField => 'sprint',
                $typeField => 'type'
            ]);
    }
}
