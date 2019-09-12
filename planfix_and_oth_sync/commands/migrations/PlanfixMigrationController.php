<?php
namespace app\commands\migrations;

use yii\console\controllers\MigrateController;

class PlanfixMigrationController extends MigrateController
{
    public $db = 'dbPlanfixSync';

    public $migrationPath = '@app/migrations/planfix';

    public $migrationNamespaces = [
        'yii\queue\db\migrations',
    ];
}
