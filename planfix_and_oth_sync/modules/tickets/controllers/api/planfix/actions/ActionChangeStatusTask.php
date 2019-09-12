<?php

namespace app\modules\tickets\controllers\api\planfix\actions;

use app\modules\tickets\forms\planfix\ChangeStatusTaskForm;
use app\modules\tickets\jobs\OnChangeStatusTaskJob;
use app\modules\tickets\services\JobSyncService;
use Throwable;
use Yii;
use yii\base\Action;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ActionChangeStatusTask extends Action
{
    /** @var ChangeStatusTaskForm */
    private $changeStatusTaskForm;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        string $id,
        Controller $controller,
        ChangeStatusTaskForm $changeStatusTaskForm,
        JobSyncService $jobSyncService,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);

        $this->changeStatusTaskForm = $changeStatusTaskForm;
        $this->jobSyncService = $jobSyncService;

        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function run()
    {
        if ($this->changeStatusTaskForm->load(Yii::$app->request->post(), '') && $this->changeStatusTaskForm->validate()) {
            try {
                $this->jobSyncService->addJob(
                    OnChangeStatusTaskJob::class,
                    ['changeStatusTaskDTO' => $this->changeStatusTaskForm->getDto()]
                );
            } catch (Throwable $exception) {
                Yii::error($exception);
                throw $exception;
            }
        } else {
            throw new BadRequestHttpException();
        }
    }
}
