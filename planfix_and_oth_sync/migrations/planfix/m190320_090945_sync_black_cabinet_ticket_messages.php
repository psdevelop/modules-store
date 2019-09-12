<?php

use yii\db\Migration;

/**
 * Class m190320_090945_sync_black_cabinet_ticket_messages
 */
class m190320_090945_sync_black_cabinet_ticket_messages extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('sync_black_cabinet_ticket_messages', [
            'id' => $this->primaryKey(),
            'ticket_message_id' => $this->integer(11),
            'ticket_message_created' => $this->dateTime(),
            'ticket_message_modified' => $this->dateTime(),
            'planfix_message_id' => $this->integer(11),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('sync_black_cabinet_ticket_messages');
    }
}
