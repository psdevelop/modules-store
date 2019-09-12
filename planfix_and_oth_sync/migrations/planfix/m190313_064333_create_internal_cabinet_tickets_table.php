<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%internal_cabinet_tickets}}`.
 */
class m190313_064333_create_internal_cabinet_tickets_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('sync_cabinet_tickets', [
            'id' => $this->primaryKey(),
            'ticket_id' => $this->integer(11),
            'ticket_created' => $this->dateTime(),
            'ticket_modified' => $this->dateTime(),
            'planfix_id' => $this->integer(11),
            'planfix_task_hash' => $this->string(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('sync_cabinet_tickets');
    }
}
