<?php

namespace app\modules\tickets\controllers\api\leads;

use app\modules\tickets\controllers\api\leads\actions\ActionAddHiddenMessage;
use app\modules\tickets\controllers\api\leads\actions\ActionAddNewMessage;
use app\modules\tickets\controllers\api\leads\actions\ActionAddNewTicket;
use app\modules\tickets\controllers\api\leads\actions\ActionChangeStatus;
use app\modules\tickets\controllers\api\leads\actions\ActionUpdateMessage;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\rest\Controller;

/**
 * Class LeadsController
 * @package app\modules\tickets\controllers\api\leads
 */
class LeadsController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'change-status'  => ['post'],
                    'add-new-message' => ['post'],
                    'add-new-ticket' => ['post'],
                    'add-hidden-message' => ['post'],
                    'update-ticket-message' => ['post'],
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
            'change-status' => ActionChangeStatus::class,
            'add-new-message' => ActionAddNewMessage::class,
            'add-new-ticket' => ActionAddNewTicket::class,
            'add-hidden-message' => ActionAddHiddenMessage::class,
            'update-ticket-message' => ActionUpdateMessage::class,
        ];
    }

    public function afterAction($action, $result)
    {
        return [
            'status' => 'success',
        ];
    }
}
