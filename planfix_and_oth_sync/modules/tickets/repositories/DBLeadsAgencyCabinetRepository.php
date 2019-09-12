<?php

namespace app\modules\tickets\repositories;

use Yii;
use yii\db\Connection;

class DBLeadsAgencyCabinetRepository extends AbstractDBExternalAgencyCabinetRepository
{
    protected function getDbConnection(): Connection
    {
        return Yii::$app->dbLeads;
    }
}
