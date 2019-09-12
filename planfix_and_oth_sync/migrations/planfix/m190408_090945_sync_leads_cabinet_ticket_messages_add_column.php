<?php

use yii\db\Migration;

/**
 * Class m190408_090945_sync_black_cabinet_ticket_messages_add_column
 */
class m190408_090945_sync_leads_cabinet_ticket_messages_add_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->addColumn(
            'sync_cabinet_ticket_messages',
            'hash',
            $this->string()->null()->after('planfix_message_id')
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropColumn('sync_cabinet_ticket_messages', 'hash');
    }
}
