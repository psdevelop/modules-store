<?php

namespace app\components\filters;

use yii\base\ActionFilter;
use yii\base\Exception;

class UniqueAccess extends ActionFilter
{
    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws Exception
     */
    public function beforeAction($action)
    {
        if (\Yii::$app->mutex->acquire($action->getUniqueId())) {
            return true;
        }
        throw new Exception(\Yii::t('app', 'Duplicate run command'));
    }
}
