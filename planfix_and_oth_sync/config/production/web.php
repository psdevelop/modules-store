<?php

use app\modules\tickets\TicketModule;
use app\modules\contacts\Module as ContactModule;
use yii\mutex\MysqlMutex;
use yii\queue\db\Queue;

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__) . '/../',
    'bootstrap' => [
        'log',
        'queue',
    ],
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
        'request' => [
            'cookieValidationKey' => 'UQXkcZX9Ttnl4fL0jcfviwJYXBcM4XDm',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'response' => [
            'class' => 'yii\web\Response',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db/db.php'),
        'dbLeads' => require(__DIR__ . '/db/dbLeads.php'),
        'dbTradeLeads' => require(__DIR__ . '/db/dbTradeLeads.php'),
        'dbPlanfixSync' => require(__DIR__ . '/db/dbPlanfixSync.php'),
        'urlManager' => require(__DIR__ . '/../common/url_manager.php'),
        'queue' => [
            'class' => Queue::class,
            'db' => 'dbPlanfixSync',
            'tableName' => '{{%queue}}',
            'channel' => 'default',
            'mutex' => MysqlMutex::class
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
