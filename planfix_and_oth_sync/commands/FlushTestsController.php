<?php

namespace app\commands;

use app\components\helpers\TimerHelper;
use app\models\planfix\PlanfixTask;
use Yii;
use yii\console\Controller;

class FlushTestsController extends Controller
{
    public function beforeAction($action)
    {
        if (getenv('app_config') != 'development') {
            echo "Разрешено только на dev-окружении!";
            exit(0);
        }
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        TimerHelper::$verbose = false;
        echo("Очистка cabinet.achievements...\n");
        Yii::$app->dbLeads->createCommand()->truncateTable("cabinet.achievements")->execute();
        echo("Очистка planfix_integration.planfix_achievements_tasks_sync...\n");
        Yii::$app->dbPlanfixSync->createCommand()->truncateTable("planfix_integration.planfix_achievements_tasks_sync")->execute();

        echo("Поиск задач...\n");
        $tasks = PlanfixTask::find([
            'target' => '5941924',
        ]);

        echo("Очистка задач...\n");
        foreach ($tasks as $task) {
            $ws = Yii::$app->planfixWs;
            $command = $ws->prepareRequest(
                'task:justDeleteBundle',
                [
                    'tasksid' => $task['id'],
                ]
            );
            $ws->sendRequest($command);
            echo '.';
        }
        echo "\nDone!\n";



    }
}
