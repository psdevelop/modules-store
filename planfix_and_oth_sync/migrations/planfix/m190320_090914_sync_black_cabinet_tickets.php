<?php

use yii\db\Migration;

/**
 * Class m190320_090914_sync_black_cabinet_tickets
 */
class m190320_090914_sync_black_cabinet_tickets extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('sync_black_cabinet_tickets', [
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
        $this->dropTable('sync_black_cabinet_tickets');
    }
}
