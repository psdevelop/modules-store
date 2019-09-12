<?php
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_offers_sync`.
 */
class m170830_084600_create_planfix_offers_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_offers_sync', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'leads_id' => $this->integer(11),
            'trade_id' => $this->integer(11),
            'status_sync' => "ENUM('none', 'add', 'update')",
            'planfix_general_id' =>$this->integer(),
            'planfix_task_id' => $this->integer()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_offers_sync');
    }
}
