<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 06.09.17
 * Time: 17:51
 */

namespace app\controllers\traits;

use app\exceptions\LinkException;
use app\models\links\LinkBase;
use app\models\links\LinkAchieveTask;
use app\models\links\LinkContact;
use app\models\links\LinkOfferTask;
use yii\helpers\ArrayHelper;

trait LinkTrait
{
    /**
     * @param $model
     * @return array
     * @throws LinkException
     */
    public function handleLinkModel($model)
    {
        if (!$model instanceof LinkBase) {
            throw new LinkException(
                "Пожалуйста, обратитесь в отдел разработки!",
                500,
                [
                    "system error" => [
                        "Model " . get_class($model) . " is not instance of " . LinkBase::class . ""
                    ]
                ]
            );
        }
        $model->module = current(explode("/", $this->id));
        $model->validate();
        if ($model->hasErrors()) {
            LinkException::validationError(null, $model->errors, $model->helpView ?? null);
        }
        return $model->handle();
    }

    /**
     * Работа с конктами
     * @return array|\yii\web\Response
     */
    public function actionContact()
    {
        $model = LinkContact::make(
            \Yii::$app->request->get()
        );
        return $this->handleModel($model);
    }

    /**
     * Создание задачи по подключению трекингового домена
     * @return array|\yii\web\Response
     */
    public function actionTrackingDomainTask()
    {
        $request = \Yii::$app->request;
        $model = LinkOfferTask::make(
            array_merge($request->post(), $request->get())
        );
        return $this->handleModel($model);
    }

    /**
     * Создание задачи по подключению трекингового домена
     * @return array|\yii\web\Response
     */
    public function actionAchievements()
    {
        $request = \Yii::$app->request;
        $model = LinkAchieveTask::make(
            array_merge($request->post(), $request->get())
        );
        return $this->handleModel($model);
    }
}
