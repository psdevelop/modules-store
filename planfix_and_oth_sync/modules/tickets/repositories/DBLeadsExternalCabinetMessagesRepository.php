<?php

namespace app\modules\tickets\repositories;

use Yii;
use yii\db\Connection;

class DBLeadsExternalCabinetMessagesRepository extends AbstractDBExternalCabinetMessagesRepository
{
    protected function getDbConnection(): Connection
    {
        return Yii::$app->dbLeads;
    }
}
