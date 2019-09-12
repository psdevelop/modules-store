<?php
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_company_sync`.
 */
class m170418_120705_create_planfix_company_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_company_sync', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'planfix_userid' => $this->integer(11)->unique(),
            'leads_id' => $this->integer(11),
            'trade_id' => $this->integer(11),
            'status_sync' => "ENUM('none', 'add', 'update')",
            'type' => "ENUM('advertiser', 'affiliate')",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_company_sync');
    }
}
