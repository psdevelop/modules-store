<?php

use yii\db\Migration;

class m170531_145546_update_planfix_sync_log_type_column extends Migration
{
    public function up()
    {
        $this->addColumn('planfix_sync_log','type',$this->char(64));
    }

    public function down()
    {
        $this->dropColumn('planfix_sync_log','type');
    }
}
