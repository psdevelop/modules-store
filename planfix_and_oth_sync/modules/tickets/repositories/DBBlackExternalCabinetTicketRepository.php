<?php

namespace app\modules\tickets\repositories;

use Yii;
use yii\db\Connection;

class DBBlackExternalCabinetTicketRepository extends AbstractDBExternalCabinetTicketRepository
{
    protected function getDbConnection(): Connection
    {
        return Yii::$app->dbTradeLeads;
    }
}