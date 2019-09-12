<?php
$dbHost = getenv('trade_db_host');
$dbName = getenv('trade_db_name');
$dbUserName = getenv('trade_db_user_name');
$dbPassword = getenv('trade_db_password');

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host='.$dbHost.';port=13306;dbname='.$dbName,
    'username' => $dbUserName,
    'password' => $dbPassword,
    'charset' => 'utf8',
];
