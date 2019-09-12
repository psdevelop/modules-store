<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 12.04.17
 * Time: 10:48
 */

namespace app\models\cabinet;

/**
 * Class CabinetChatExternalAgent
 * @property string $external_id
 * @property string $name
 * @property string $email
 * @property integer $employee_id
 *
 * @package app\models\cabinet
 */
class CabinetChatExternalAgent extends CabinetBase
{

    public static $table = 'chat_external_agents';


    public function getEmployee()
    {
        $this->setDbById();
        return $this->hasOne(CabinetEmployee::className(), ['id' => 'employee_id']);
    }

}
