<?php

namespace app\modules\tickets\controllers\api\leads\actions;

use app\modules\tickets\forms\leads\AddNewMessageToTicketForm;
use app\modules\tickets\jobs\OnAddNewTicketMessageJob;
use app\modules\tickets\services\JobSyncService;
use Throwable;
use Yii;
use yii\base\Action;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ActionAddNewMessage extends Action
{
    /** @var AddNewMessageToTicketForm */
    private $addNewMessageToTicketForm;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        string $id,
        Controller $controller,
        AddNewMessageToTicketForm $addNewMessageToTicketForm,
        JobSyncService $jobSyncService,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);

        $this->addNewMessageToTicketForm = $addNewMessageToTicketForm;
        $this->jobSyncService = $jobSyncService;

        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function run()
    {
        if (!$this->addNewMessageToTicketForm->load(Yii::$app->request->post(), '')) {
            throw new BadRequestHttpException(json_encode($this->addNewMessageToTicketForm));
        }

        if (!$this->addNewMessageToTicketForm->validate()) {
            throw new BadRequestHttpException(json_encode($this->addNewMessageToTicketForm->getErrors()));
        }

        try {
            $this->jobSyncService->addJob(
                OnAddNewTicketMessageJob::class,
                ['addNewMessageToTicketDTO' => $this->addNewMessageToTicketForm->getDto()]
            );
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw $exception;
        }

    }
}
