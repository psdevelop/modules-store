<?php

namespace app\modules\tickets\helpers;

use app\modules\tickets\enum\PlanfixTaskStatusEnum;
use app\modules\tickets\enum\TicketCategoryEnum;
use app\modules\tickets\enum\TicketCategorySubEnum;
use app\modules\tickets\enum\TicketStatusEnum;
use app\modules\tickets\services\EnvironmentService;

/**
 * Класс для маппинга статусов между кабинетом и Planfix
 * Class MapperTicketPlanfix
 * @package app\modules\tickets\helpers
 */
class MapperTicketPlanfix
{
    /**
     * Маппинг заголовков задачи в Planfix
     * @var array
     */
    private static $mapTaskTitle = [
        TicketCategoryEnum::CATEGORY_AK => [
            TicketCategorySubEnum::SUB_REPLENISHMENT => 'Агентский кабинет - пополнение',
            TicketCategorySubEnum::SUB_CREATE => 'Агентский кабинет - создание',
            TicketCategorySubEnum::SUB_TRANSFER => 'Агентский кабинет - перевод',
            TicketCategorySubEnum::SUB_OTHER => 'Агентский кабинет - другое',
            TicketCategorySubEnum::SUB_MODERATION => 'Агентский кабинет - модерация',
        ],
        TicketCategoryEnum::CATEGORY_TP => [
            TicketCategorySubEnum::SUB_FREE_FORM => 'Обращение в свободной форме',
        ]
    ];

    /**
     * Статусы тикетов в кабинете
     * @var array
     */
    private static $mapStatusTicketToTask = [
        TicketStatusEnum::STATUS_NEW => PlanfixTaskStatusEnum::STATUS_NEW,
        TicketStatusEnum::STATUS_IN_PROGRESS => PlanfixTaskStatusEnum::STATUS_IN_PROGRESS,
        TicketStatusEnum::STATUS_COMPLETED => PlanfixTaskStatusEnum::STATUS_COMPLETED,
        TicketStatusEnum::STATUS_CANCELLED => PlanfixTaskStatusEnum::STATUS_CANCELLED,
        TicketStatusEnum::STATUS_MANUAL_PROCESSING => PlanfixTaskStatusEnum::STATUS_MANUAL_PROCESSING,
    ];

    /**
     * Маппинг статуса задача в Planfix в статусы тикетов в кабинете
     * @var array
     */
    private static $mapStatusTaskToTicket = [
        PlanfixTaskStatusEnum::STATUS_NEW => TicketStatusEnum::STATUS_NEW, //1 - Новая
        PlanfixTaskStatusEnum::STATUS_IN_PROGRESS => TicketStatusEnum::STATUS_IN_PROGRESS, //2- В работе
        PlanfixTaskStatusEnum::STATUS_COMPLETED => TicketStatusEnum::STATUS_COMPLETED, //3 - Завершенная
        PlanfixTaskStatusEnum::STATUS_REFUSED => TicketStatusEnum::STATUS_COMPLETED, //5 - Отклоненная
        PlanfixTaskStatusEnum::STATUS_CANCELLED => TicketStatusEnum::STATUS_COMPLETED, //7 - Отмененная
        PlanfixTaskStatusEnum::STATUS_IN_PROGRESS_2 => TicketStatusEnum::STATUS_IN_PROGRESS, //8 - В работе
        PlanfixTaskStatusEnum::STATUS_MANUAL_PROCESSING => TicketStatusEnum::STATUS_IN_PROGRESS, //146 - Ручная обработка (Custom Status)
    ];

    /**
     * Возвращает ID шаблона для задачи в Planfix
     * @param string $project
     * @param int $ticketCategoryId
     * @param int $ticketSubCategoryId
     * @return int
     */
    public static function getIdTaskTemplate(string $project, int $ticketCategoryId, int $ticketSubCategoryId): int
    {
        static $mapTask;

        if (!isset($mapTask)) {
            $mapTask = [
                EnvironmentService::PROJECT_LEADS => [
                    TicketCategoryEnum::CATEGORY_AK => [
                        TicketCategorySubEnum::SUB_REPLENISHMENT => env('PLANFIX_LEADS_SU_TEMPLATE_REPLENISHMENT'),
                        TicketCategorySubEnum::SUB_CREATE => env('PLANFIX_LEADS_SU_TEMPLATE_CREATURE'),
                        TicketCategorySubEnum::SUB_TRANSFER => env('PLANFIX_LEADS_SU_TEMPLATE_TRANSFER'),
                        TicketCategorySubEnum::SUB_OTHER => env('PLANFIX_LEADS_SU_TEMPLATE_OTHER'),
                        TicketCategorySubEnum::SUB_MODERATION => env('PLANFIX_LEADS_SU_TEMPLATE_MODERATION'),
                    ],
                    TicketCategoryEnum::CATEGORY_TP => [
                        TicketCategorySubEnum::SUB_FREE_FORM => env('PLANFIX_LEADS_SU_TEMPLATE_FREE_FORM'),
                    ],
                ],
                EnvironmentService::PROJECT_BLACK => [
                    TicketCategoryEnum::CATEGORY_AK => [
                        TicketCategorySubEnum::SUB_REPLENISHMENT => env('PLANFIX_LEADS_BLACK_TEMPLATE_REPLENISHMENT'),
                        TicketCategorySubEnum::SUB_CREATE => env('PLANFIX_LEADS_BLACK_TEMPLATE_CREATURE'),
                        TicketCategorySubEnum::SUB_TRANSFER => env('PLANFIX_LEADS_BLACK_TEMPLATE_TRANSFER'),
                        TicketCategorySubEnum::SUB_OTHER => env('PLANFIX_LEADS_BLACK_TEMPLATE_OTHER'),
                        TicketCategorySubEnum::SUB_MODERATION => env('PLANFIX_LEADS_BLACK_TEMPLATE_MODERATION'),
                    ],
                    TicketCategoryEnum::CATEGORY_TP => [
                        TicketCategorySubEnum::SUB_FREE_FORM => env('PLANFIX_LEADS_BLACK_TEMPLATE_FREE_FORM'),
                    ],
                ],
            ];
        }

        return $mapTask[$project][$ticketCategoryId][$ticketSubCategoryId];
    }

    /**
     * Возвращает Id бизнес-процесса для задачи в Planfix
     * @param string $project
     * @param int $ticketCategoryId
     * @param int $ticketSubCategoryId
     * @return int
     */
    public static function getIdProcessTemplate(string $project, int $ticketCategoryId, int $ticketSubCategoryId): int
    {
         static $mapTaskProcess;

         if (!isset($mapTaskProcess)) {
             $mapTaskProcess = [
                 EnvironmentService::PROJECT_LEADS => [
                     TicketCategoryEnum::CATEGORY_AK => [
                         TicketCategorySubEnum::SUB_REPLENISHMENT => env('PLANFIX_LEADS_SU_PROCESS_REPLENISHMENT'),
                         TicketCategorySubEnum::SUB_CREATE => env('PLANFIX_LEADS_SU_PROCESS_CREATURE'),
                         TicketCategorySubEnum::SUB_TRANSFER => env('PLANFIX_LEADS_SU_PROCESS_TRANSFER'),
                         TicketCategorySubEnum::SUB_OTHER => env('PLANFIX_LEADS_SU_PROCESS_OTHER'),
                         TicketCategorySubEnum::SUB_MODERATION => env('PLANFIX_LEADS_SU_PROCESS_MODERATION'),
                     ],
                     TicketCategoryEnum::CATEGORY_TP => [
                         TicketCategorySubEnum::SUB_FREE_FORM => env('PLANFIX_LEADS_SU_PROCESS_FREE_FORM'),
                     ],
                 ],
                 EnvironmentService::PROJECT_BLACK => [
                     TicketCategoryEnum::CATEGORY_AK => [
                         TicketCategorySubEnum::SUB_REPLENISHMENT => env('PLANFIX_LEADS_SU_PROCESS_REPLENISHMENT'),
                         TicketCategorySubEnum::SUB_CREATE => env('PLANFIX_LEADS_SU_PROCESS_CREATURE'),
                         TicketCategorySubEnum::SUB_TRANSFER => env('PLANFIX_LEADS_SU_PROCESS_TRANSFER'),
                         TicketCategorySubEnum::SUB_OTHER => env('PLANFIX_LEADS_SU_PROCESS_OTHER'),
                         TicketCategorySubEnum::SUB_MODERATION => env('PLANFIX_LEADS_SU_PROCESS_MODERATION'),
                     ],
                     TicketCategoryEnum::CATEGORY_TP => [
                         TicketCategorySubEnum::SUB_FREE_FORM => env('PLANFIX_LEADS_SU_PROCESS_FREE_FORM'),
                     ]
                 ],
             ];
         }

        return $mapTaskProcess[$project][$ticketCategoryId][$ticketSubCategoryId];
    }

    /**
     * Возвращает ID группы пользователей Planfix (для исполнителя задачи по тикету по умолчанию)
     * @param string $project
     * @param int $ticketCategoryId
     * @param int $ticketSubCategoryId
     * @return int
     */
    public static function getDefaultUserGroupAsWorker(string $project, int $ticketCategoryId, int $ticketSubCategoryId): int
    {
        static $mapWorkerId;

        if (!isset($mapWorkerId)) {
            $mapWorkerId = [
                EnvironmentService::PROJECT_LEADS => [
                    TicketCategoryEnum::CATEGORY_AK => [
                        TicketCategorySubEnum::SUB_REPLENISHMENT => env('PLANFIX_LEADS_SU_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_CREATE => env('PLANFIX_LEADS_SU_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_TRANSFER => env('PLANFIX_LEADS_SU_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_OTHER => env('PLANFIX_LEADS_SU_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_MODERATION => env('PLANFIX_LEADS_SU_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                    ],
                    TicketCategoryEnum::CATEGORY_TP => [
                        TicketCategorySubEnum::SUB_FREE_FORM => env('PLANFIX_LEADS_SU_ACCOUNT_MANAGERS_GROUP_ID'),
                    ],
                ],
                EnvironmentService::PROJECT_BLACK => [
                    TicketCategoryEnum::CATEGORY_AK => [
                        TicketCategorySubEnum::SUB_REPLENISHMENT => env('PLANFIX_LEADS_BLACK_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_CREATE => env('PLANFIX_LEADS_BLACK_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_TRANSFER => env('PLANFIX_LEADS_BLACK_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_OTHER => env('PLANFIX_LEADS_BLACK_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                        TicketCategorySubEnum::SUB_MODERATION => env('PLANFIX_LEADS_BLACK_AGENCY_CABINET_MANAGERS_GROUP_ID'),
                    ],
                    TicketCategoryEnum::CATEGORY_TP => [
                        TicketCategorySubEnum::SUB_FREE_FORM => env('PLANFIX_LEADS_BLACK_ACCOUNT_MANAGERS_GROUP_ID'),
                    ],
                ],
            ];
        }

        return $mapWorkerId[$project][$ticketCategoryId][$ticketSubCategoryId];
    }

    /**
     * Возвращает Id проекта по его названию
     * @param string $title
     * @return string
     */
    public static function getProjectIdByTitle(string $title): string
    {
        static $mapProjectNameToId;

        if (!isset($mapProjectNameToId)) {
            $mapProjectNameToId = [
                EnvironmentService::PROJECT_LEADS => env('PLANFIX_LEADS_SU_PROJECT'),
                EnvironmentService::PROJECT_BLACK => env('PLANFIX_LEADS_BLACK_PROJECT'),
            ];
        }

        return $mapProjectNameToId[$title];
    }

    /**
     * Возаращает заголовок для задачи в Planfix
     * @param int $ticketCategoryId
     * @param int $ticketSubCategoryId
     * @return string
     */
    public static function getTitleTask(int $ticketCategoryId, int $ticketSubCategoryId): string
    {
        return self::$mapTaskTitle[$ticketCategoryId][$ticketSubCategoryId];
    }

    /**
     * Возвращает статус задачи в Planfix
     * @param string $ticketStatus
     * @return string
     */
    public static function getStatusTask(string $ticketStatus): string
    {
        return self::$mapStatusTicketToTask[$ticketStatus];
    }

    /**
     * Возвращает статус для тикета в кабинете
     * @param int $taskStatus
     * @return string
     */
    public static function getStatusTicket(int $taskStatus): string
    {
        return self::$mapStatusTaskToTicket[$taskStatus] ?? TicketStatusEnum::STATUS_IN_PROGRESS;
    }
}
