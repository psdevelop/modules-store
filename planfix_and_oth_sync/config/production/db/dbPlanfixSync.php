<?php
$dbHost = getenv('planfix_db_host');
$dbName = getenv('planfix_db_name');
$dbUserName = getenv('planfix_db_user_name');
$dbPassword = getenv('planfix_db_password');

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host='.$dbHost.';port=13306;dbname='.$dbName,
    'username' => $dbUserName,
    'password' => $dbPassword,
    'charset' => 'utf8',
];
