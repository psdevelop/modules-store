<?php

namespace app\modules\tickets\repositories;

use Yii;
use yii\db\Connection;

class DBBlackExternalCabinetMessagesRepository extends AbstractDBExternalCabinetMessagesRepository
{
    protected function getDbConnection(): Connection
    {
        return Yii::$app->dbTradeLeads;
    }
}
