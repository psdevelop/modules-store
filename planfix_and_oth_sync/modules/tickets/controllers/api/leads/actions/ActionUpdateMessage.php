<?php

namespace app\modules\tickets\controllers\api\leads\actions;

use app\modules\tickets\forms\leads\UpdateMessageInTicketForm;
use app\modules\tickets\jobs\OnUpdateTicketMessageJob;
use app\modules\tickets\services\JobSyncService;
use Throwable;
use Yii;
use yii\base\Action;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ActionUpdateMessage extends Action
{
    /** @var UpdateMessageInTicketForm */
    private $updateMessageInTicketForm;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        string $id,
        Controller $controller,
        UpdateMessageInTicketForm $updateMessageInTicketForm,
        JobSyncService $jobSyncService,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);

        $this->updateMessageInTicketForm = $updateMessageInTicketForm;
        $this->jobSyncService = $jobSyncService;

        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function run()
    {
        if (!$this->updateMessageInTicketForm->load(Yii::$app->request->post(), '')) {
            throw new BadRequestHttpException(json_encode($this->updateMessageInTicketForm));
        }

        if (!$this->updateMessageInTicketForm->validate()) {
            throw new BadRequestHttpException(json_encode($this->updateMessageInTicketForm->getErrors()));
        }

        try {
            $this->jobSyncService->addJob(
                OnUpdateTicketMessageJob::class,
                ['updateMessageInTicketDTO' => $this->updateMessageInTicketForm->getDto()]
            );
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw $exception;
        }

    }
}
