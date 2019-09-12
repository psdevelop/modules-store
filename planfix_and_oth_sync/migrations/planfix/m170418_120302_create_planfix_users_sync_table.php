<?php
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_users_sync`.
 */
class m170418_120302_create_planfix_users_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_users_sync', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'planfix_userid' => $this->integer(11)->unique(),
            'leads_id' => $this->integer(11),
            'trade_id' => $this->integer(11),
            'status_sync' => "ENUM('none', 'add', 'update')",
            'leads_cid' => $this->integer(11),
            'trade_cid' => $this->integer(11),
            'type' => "ENUM('advertiser', 'affiliate')",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_users_sync');
    }
}
