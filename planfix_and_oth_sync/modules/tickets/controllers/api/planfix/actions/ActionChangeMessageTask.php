<?php

namespace app\modules\tickets\controllers\api\planfix\actions;

use app\modules\tickets\forms\planfix\AddNewMessageToTaskForm;
use app\modules\tickets\jobs\OnChangeMessageTaskJob;
use app\modules\tickets\services\JobSyncService;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Throwable;
use Yii;
use yii\base\Action;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ActionChangeMessageTask extends Action
{
    /** @var AddNewMessageToTaskForm */
    private $addNewMessageToTaskForm;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        string $id,
        Controller $controller,
        AddNewMessageToTaskForm $addNewMessageToTaskForm,
        JobSyncService $jobSyncService,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);

        $this->addNewMessageToTaskForm = $addNewMessageToTaskForm;
        $this->jobSyncService = $jobSyncService;

        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function run()
    {
        if ($this->addNewMessageToTaskForm->load(Yii::$app->request->post(), '') && $this->addNewMessageToTaskForm->validate()) {
            try {
                $this->jobSyncService->addJob(
                    OnChangeMessageTaskJob::class,
                    ['addNewMessageToTaskDTO' => $this->addNewMessageToTaskForm->getDto()]
                );
            } catch (Throwable $exception) {
                Yii::error($exception);

                throw new InternalErrorException();
            }
        } else {
            throw new BadRequestHttpException();
        }
    }
}
