<?php

namespace app\modules\tickets\repositories;

use Yii;
use yii\db\Connection;

class DBBlackAgencyCabinetRepository extends AbstractDBExternalAgencyCabinetRepository
{
    protected function getDbConnection(): Connection
    {
        return Yii::$app->dbTradeLeads;
    }
}
