<?php

namespace app\modules\contacts\repositories;

use Yii;
use yii\db\Connection;

class BlackContactsSync extends ContactsSync
{
    /** @inheritDoc */
    public function getConnectionName(): string
    {
        return 'dbTradeLeads';
    }

}