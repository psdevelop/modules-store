<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%internal_cabinet_ticket_messages}}`.
 */
class m190314_062849_create_internal_cabinet_ticket_messages_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('sync_cabinet_ticket_messages', [
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
        $this->dropTable('sync_cabinet_ticket_messages');
    }
}
