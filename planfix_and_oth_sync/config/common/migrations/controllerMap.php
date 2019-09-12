<?php

return [
    'migrate-planfix' => [
        'class' => 'app\commands\migrations\PlanfixMigrationController',
    ],
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationPath' => '@app/migrations/',
    ]
];