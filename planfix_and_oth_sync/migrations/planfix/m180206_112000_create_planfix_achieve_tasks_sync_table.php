<?php
use app\models\planfix\PlanfixTask;
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_offers_tasks_sync`.
 */
class m180206_112000_create_planfix_achieve_tasks_sync_table extends Migration
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
        $this->createTable('planfix_achievements_tasks_sync', [
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
        $this->dropTable('planfix_achievements_tasks_sync');
    }
}
