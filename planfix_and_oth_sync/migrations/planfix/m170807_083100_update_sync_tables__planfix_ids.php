<?php

use yii\db\Migration;

class m170807_083100_update_sync_tables__planfix_ids extends Migration
{
    public $tablesForGeneral = [
        'planfix_company_sync',
        'planfix_users_sync',
        'planfix_chats_sync',
        'planfix_payments_requests',
        'planfix_unknown_users_sync',
    ];

    public $tablesForTaskId = [
        'planfix_company_sync',
        'planfix_users_sync',
    ];

    public function up()
    {
        foreach ($this->tablesForGeneral as $table) {
            $this->addColumn($table, 'planfix_general_id', $this->integer());
        }
        foreach ($this->tablesForTaskId as $table) {
            $this->addColumn($table, 'planfix_task_id', $this->integer());
        }

    }

    public function down()
    {
        foreach ($this->tablesForGeneral as $table) {
            $this->dropColumn($table, 'planfix_general_id');
        }
        foreach ($this->tablesForTaskId as $table) {
            $this->dropColumn($table, 'planfix_task_id');
        }
    }
}
