<?php

use app\modules\contacts\Module as ContactModule;
use app\bootstrap\DIContainer;
use app\modules\tickets\TicketModule;
use yii\mutex\FileMutex;
use yii\queue\db\Queue;

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__) . '/../',
    'bootstrap' => [
        'log',
        'queue',
        DIContainer::class,
    ],
    'controllerNamespace' => 'app\commands',
    'controllerMap' => require(__DIR__ . '/migrations/controllerMap.php'),
    'aliases' => [
        '@runtime' => dirname(__DIR__) . '/../../runtime',
    ],
    'components' => [
        'planfixApi' => require(__DIR__ . '/modules/planfixApi.php'),
        'planfixWs' => require(__DIR__ . '/modules/planfixWebService.php'),
        'cabinetApi' => require(__DIR__ . '/modules/cabinetApi.php'),
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache',
        ],
        'log' => require(__DIR__ . '/../common/log_config.php'),
        'mutex' => [
            'class' => FileMutex::class,
        ],
        'db' => require(__DIR__ . '/db/db.php'),
        'dbLeads' => require(__DIR__ . '/db/dbLeads.php'),
        'dbTradeLeads' => require(__DIR__ . '/db/dbTradeLeads.php'),
        'dbPlanfixSync' => require(__DIR__ . '/db/dbPlanfixSync.php'),
        'queue' => [
            'class' => Queue::class,
            'db' => 'dbPlanfixSync',
            'tableName' => '{{%queue}}',
            'channel' => 'default',
        ],
    ],
    'modules' => [
        'tickets' => [
            'class' => TicketModule::class,
        ],
        'contacts' => [
            'class' => ContactModule::class,
        ],
    ],
    'params' => require(__DIR__ . '/params.php'),
];

return $config;
