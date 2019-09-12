<?php

namespace app\modules\contacts\repositories;

use Yii;
use yii\db\Connection;

class LeadsContactsSync extends ContactsSync
{
    /** @inheritDoc */
    public function getConnectionName(): string
    {
        return 'dbLeads';
    }

}