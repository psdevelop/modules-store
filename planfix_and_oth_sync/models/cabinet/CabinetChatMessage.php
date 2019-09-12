<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 12.04.17
 * Time: 10:48
 */

namespace app\models\cabinet;

/**
 * Class CabinetChat
 * @property integer $id
 * @property string $created
 * @property integer $chat_id
 * @property string $message_type
 * @property string $message
 * @package app\models\cabinet
 */
class CabinetChatMessage extends CabinetBase
{
    public static $table = 'chat_messages';
    public static $createdField = 'created';
    public $planfixTaskId;
    public $planfixTaskGeneral;
    public $planfixChatWorkers;
    public $planfixChatClient;

    public function getChat()
    {
        $this->setDbById();
        return $this->hasOne(CabinetChat::className(), ['id' => 'chat_id']);
    }

    public function prepareMessage()
    {
        return $this->message;
    }

}
