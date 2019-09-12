<?php

namespace app\components\helpers;

use yii\db\Connection;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 21.04.17
 * Time: 16:12
 */
class DbHelper
{
    public static function getDbName(Connection $connection)
    {
        $data = explode(';', $connection->dsn);
        foreach ($data as $item) {
            if (strpos($item, 'dbname') !== false) {
                $dbName = explode('=', $item);
                return $dbName[1];
            }
        }
        return false;
    }
}
