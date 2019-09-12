<?php
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_offers_tasks_sync`.
 */
class m170906_100700_create_planfix_offers_tasks_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_offers_tasks_sync', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'leads_id' => $this->integer(11),
            'trade_id' => $this->integer(11),
            'status_sync' => "ENUM('none', 'add', 'update')",
            'planfix_general_id' =>$this->integer(),
            'planfix_task_id' => $this->integer(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_offers_tasks_sync');
    }
}
