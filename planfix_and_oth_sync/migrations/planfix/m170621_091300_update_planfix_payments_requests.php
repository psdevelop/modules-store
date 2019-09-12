<?php

use yii\db\Migration;

class m170621_091300_update_planfix_payments_requests extends Migration
{
    public $table = 'planfix_payments_requests';
    public function up()
    {
        $this->addColumn($this->table,'sum_commission',$this->float());
    }

    public function down()
    {
        $this->dropColumn($this->table,'planfix_sync_log');
    }
}
