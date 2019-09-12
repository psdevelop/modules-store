<?php

use yii\db\Migration;

/**
 * Handles the creation of table `planfix_payments_requests`.
 */
class m170530_102031_create_planfix_payments_requests_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_payments_requests', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'leads_id' => $this->integer(11),
            'trade_id' => $this->integer(11),
            'status_sync' => "ENUM('none', 'add', 'update')",
            'status_payment' => "ENUM('new','need_approve', 'pending', 'rejected','paid','closed')",
            'sum' => $this->float()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_payments_requests');
    }
}
