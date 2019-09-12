<?php
$dbHost = getenv('leads_db_host');
$dbName = getenv('leads_db_name');
$dbUserName = getenv('leads_db_user_name');
$dbPassword = getenv('leads_db_password');

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host='.$dbHost.';port=13306;dbname='.$dbName,
    'username' => $dbUserName,
    'password' => $dbPassword,
    'charset' => 'utf8',
];
