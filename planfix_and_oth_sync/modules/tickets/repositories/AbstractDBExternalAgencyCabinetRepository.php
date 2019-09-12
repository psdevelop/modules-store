<?php

namespace app\modules\tickets\repositories;

use Yii;
use yii\db\Connection;

abstract class AbstractDBExternalAgencyCabinetRepository
{
    /** @var Connection  */
    protected $dbConnection;

    public function __construct()
    {
        $this->dbConnection = $this->getDbConnection();
    }

    protected abstract function getDbConnection(): Connection;

    public function activateAgencyCabinet($agencyCabinetId)
    {
        $this->dbConnection->createCommand()->update(
            'agency_cabinet',
            ['status' => 'active'],
            ['id' => $agencyCabinetId]
        )->execute();
    }
}
