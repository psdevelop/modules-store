<?php

use yii\db\Migration;

class m170503_123934_update_planfix_unknown_users_sync_table extends Migration
{
    public function up()
    {
        $this->addColumn('planfix_unknown_users_sync','name',$this->char(64));
    }

    public function down()
    {
        $this->dropColumn('planfix_unknown_users_sync','name');
        return false;
    }
}
