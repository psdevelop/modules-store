<?php

namespace app\modules\tickets\repositories;

use Yii;
use yii\db\Connection;

class DBLeadsExternalCabinetTicketRepository extends AbstractDBExternalCabinetTicketRepository
{
    protected function getDbConnection(): Connection
    {
        return Yii::$app->dbLeads;
    }
}
