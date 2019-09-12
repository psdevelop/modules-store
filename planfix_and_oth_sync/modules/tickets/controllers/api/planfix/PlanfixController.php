<?php

namespace app\modules\tickets\controllers\api\planfix;

use app\modules\tickets\controllers\api\planfix\actions\ActionAddNewMessageToTask;
use app\modules\tickets\controllers\api\planfix\actions\ActionChangeMessageTask;
use app\modules\tickets\controllers\api\planfix\actions\ActionChangeStatusTask;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\rest\Controller;

class PlanfixController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'change-status'  => ['post'],
                    'add-new-message' => ['post'],
                    'change-message' => ['post'],
                ],
            ],
            'authenticator' => [
                'class' => QueryParamAuth::class,
                'tokenParam' => 'hash',
            ]
        ];
    }

    /**
     * @return array[][]
     */
    public function actions(): array
    {
        return [
            'change-status' => ActionChangeStatusTask::class,
            'add-new-message' => ActionAddNewMessageToTask::class,
            'change-message' => ActionChangeMessageTask::class,
        ];
    }

    public function afterAction($action, $result)
    {
        return [
            'status' => 'success',
        ];
    }
}
