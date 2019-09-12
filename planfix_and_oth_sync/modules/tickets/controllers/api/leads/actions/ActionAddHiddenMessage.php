<?php

namespace app\modules\tickets\controllers\api\leads\actions;

use app\modules\tickets\forms\leads\AddHiddenMessageToTicketForm;
use app\modules\tickets\jobs\OnAddTicketHiddenMessageJob;
use app\modules\tickets\services\JobSyncService;
use Throwable;
use Yii;
use yii\base\Action;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class ActionAddHiddenMessage
 * @package app\modules\tickets\controllers\api\leads\actions
 */
class ActionAddHiddenMessage extends Action
{
    /** @var AddHiddenMessageToTicketForm */
    private $addHiddenMessageToTicketForm;

    /** @var JobSyncService */
    private $jobSyncService;

    public function __construct(
        string $id,
        Controller $controller,
        AddHiddenMessageToTicketForm $addHiddenMessageToTicketForm,
        JobSyncService $jobSyncService,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);

        $this->addHiddenMessageToTicketForm = $addHiddenMessageToTicketForm;
        $this->jobSyncService = $jobSyncService;

        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    /**
     * @throws Throwable
     */
    public function run()
    {
        if (!$this->addHiddenMessageToTicketForm->load(Yii::$app->request->post(), '')
            || !$this->addHiddenMessageToTicketForm->validate()) {
            throw new BadRequestHttpException();
        }

        try {
            $this->jobSyncService->addJob(
                OnAddTicketHiddenMessageJob::class,
                ['addHiddenMessageToTicketDTO' => $this->addHiddenMessageToTicketForm->getDto()]
            );
        } catch (Throwable $exception) {
            Yii::error($exception);
            throw $exception;
        }
    }
}
