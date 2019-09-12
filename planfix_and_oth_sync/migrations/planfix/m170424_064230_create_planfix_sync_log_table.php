<?php
use yii\db\Migration;

/**
 * Handles the creation of table `planfix_sync_log`.
 */
class m170424_064230_create_planfix_sync_log_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('planfix_sync_log', [
            'id' => $this->primaryKey(),
            'created' => $this->dateTime(),
            'date_from' => $this->dateTime(),
            'date_to' => $this->dateTime(),
            'elapsed_time' => $this->float(3),
            'is_success' => $this->boolean(),
            'message' => $this->text()->null()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('planfix_sync_log');
    }
}
