<?php
use app\models\planfix\PlanfixTask;
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_tickets_sync`.
 */
class m190227_142900_create_planfix_tickets_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $statusesPlanfix = "'" . implode("','", [
                PlanfixTask::TASK_STATUS_COMPLETED,
                PlanfixTask::TASK_STATUS_WORK,
                PlanfixTask::TASK_STATUS_REJECTED,
            ]) . "'";
        $this->createTable('planfix_tickets_sync', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'leads_id' => $this->integer(11),
            'trade_id' => $this->integer(11),
            'status_sync' => "ENUM('none', 'add', 'update')",
            'status_task' => "ENUM($statusesPlanfix)",
            'planfix_general_id' => $this->integer(),
            'planfix_task_id' => $this->integer(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_tickets_sync');
    }
}
