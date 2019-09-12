<?php

namespace app\modules\tickets;

use app\modules\tickets\controllers\api\leads\LeadsController;
use app\modules\tickets\controllers\api\planfix\PlanfixController;
use Yii;
use yii\base\Module as BaseModule;
use yii\console\Application;

class TicketModule extends BaseModule
{
    const NAMESPACE_COMMANDS = 'app\modules\tickets\commands';

    public $controllerMap = [
        'leads' => LeadsController::class,
        'planfix' => PlanfixController::class,
    ];

    public function init()
    {
        parent::init();

        if (Yii::$app instanceof Application) {
            $this->controllerNamespace = self::NAMESPACE_COMMANDS;
        }
    }
}
