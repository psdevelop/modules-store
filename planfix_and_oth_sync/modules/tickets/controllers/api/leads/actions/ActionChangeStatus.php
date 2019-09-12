<?php

namespace app\modules\tickets\controllers\api\leads\actions;

use app\modules\tickets\forms\leads\ChangeStatusTicketForm;
use app\modules\tickets\jobs\OnChangeStatusTicketJob;
use app\modules\tickets\services\JobSyncService;
use Throwable;
use Yii;
use yii\base\Action;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ActionChangeStatus extends Action
{
    /** @var ChangeStatusTicketForm */
    private $changeStatusForm;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        string $id,
        Controller $controller,
        ChangeStatusTicketForm $changeStatusForm,
        JobSyncService $jobSyncService,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);

        $this->changeStatusForm = $changeStatusForm;
        $this->jobSyncService = $jobSyncService;

        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function run()
    {
        if (!$this->changeStatusForm->load(Yii::$app->request->post(), '') ||
            !$this->changeStatusForm->validate()) {
            throw new BadRequestHttpException();
        }

        try {
            $this->jobSyncService->addJob(
                OnChangeStatusTicketJob::class,
                ['changeStatusTicketDTO' => $this->changeStatusForm->getDto()]
            );
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw $exception;
        }
    }
}
