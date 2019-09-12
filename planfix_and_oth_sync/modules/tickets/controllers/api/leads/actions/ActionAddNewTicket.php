<?php

namespace app\modules\tickets\controllers\api\leads\actions;

use app\modules\tickets\forms\leads\AddNewTicketForm;
use app\modules\tickets\jobs\OnAddNewTicketJob;
use app\modules\tickets\services\JobSyncService;
use Throwable;
use Yii;
use yii\base\Action;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ActionAddNewTicket extends Action
{
    /** @var AddNewTicketForm */
    private $addNewTicketForm;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        string $id,
        Controller $controller,
        AddNewTicketForm $addNewTicketForm,
        JobSyncService $jobSyncService,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);

        $this->addNewTicketForm = $addNewTicketForm;
        $this->jobSyncService = $jobSyncService;

        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function run()
    {
        if (!$this->addNewTicketForm->load(Yii::$app->request->post(), '')
            || !$this->addNewTicketForm->validate()) {
            throw new BadRequestHttpException();
        }

        try {
            $this->jobSyncService->addJob(
                OnAddNewTicketJob::class,
                ['addNewTicketDTO' => $this->addNewTicketForm->getDto()]
            );
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw $exception;
        }

    }
}
