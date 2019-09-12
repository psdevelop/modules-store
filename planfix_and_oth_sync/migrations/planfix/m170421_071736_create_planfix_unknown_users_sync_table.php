<?php
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_unknown_users_sync`.
 */
class m170421_071736_create_planfix_unknown_users_sync_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_unknown_users_sync', [
            'id' => $this->primaryKey(),
            'planfix_id' => $this->integer(11)->unique(),
            'planfix_userid' => $this->integer(11)->unique(),
            'email' => $this->char(64)
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_unknown_users_sync');
    }
}
