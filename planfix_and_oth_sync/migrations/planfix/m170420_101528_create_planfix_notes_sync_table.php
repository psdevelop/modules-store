<?php
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_notes_sync`.
 */
class m170420_101528_create_planfix_notes_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_notes_sync', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'leads_id' => $this->integer(11),
            'trade_id' => $this->integer(11),
            'status_sync' => "ENUM('none', 'add', 'update')",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_notes_sync');
    }
}
