<?php

namespace app\modules\tickets\commands;

use app\components\filters\UniqueAccess;
use app\modules\tickets\services\EnvironmentService;
use app\modules\tickets\services\SyncMessageService;
use app\modules\tickets\services\SyncTicketService;
use Exception;
use Yii;
use yii\base\Module;
use yii\console\Controller;

class SyncTicketsController extends Controller
{
    /** @var EnvironmentService */
    private $environmentService;

    /** @var string[]  */
    private $allowTypeSync;

    public function __construct(
        string $id,
        Module $module,
        array $config = [],
        EnvironmentService $environmentService
    ) {
        $this->environmentService = $environmentService;

        $this->allowTypeSync = [
            EnvironmentService::PROJECT_LEADS,
            EnvironmentService::PROJECT_BLACK,
        ];

        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'UniqueAccess' => [
                'class' => UniqueAccess::class,
            ]
        ];
    }

    /**
     * Синхноризация тикетов и комментариев из leads в planfix
     * @param string $type - ('leads' или 'black')
     */
    public function actionSyncLeadsToPlanfix(string $type = 'leads')
    {
        if (! in_array($type, $this->allowTypeSync)) {
            throw new Exception('Invalid param');
        }
        $this->environmentService->setEnvironment($type);

        Yii::$container->get(SyncTicketService::class)->syncTicketToPlanfix();
        Yii::$container->get(SyncMessageService::class)->syncLeadsToPlanfix();
    }

    /**
     * Синхноризация задач и комментариев из planfix в leads
     * @param string $type - ('leads' или 'black')
     */
    public function actionSyncPlanfixToLeads(string $type = 'leads')
    {
        if (! in_array($type, $this->allowTypeSync)) {
            throw new Exception('Invalid param');
        }

        $this->environmentService->setEnvironment($type);

        Yii::$container->get(SyncTicketService::class)->syncPlanfixToTicket();
        Yii::$container->get(SyncMessageService::class)->syncPlanfixToLeads();
    }
}
