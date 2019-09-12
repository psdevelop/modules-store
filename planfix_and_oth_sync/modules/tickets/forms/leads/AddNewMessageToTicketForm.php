<?php

namespace app\modules\tickets\forms\leads;

use app\modules\tickets\dto\AddNewMessageToTicketDTO;
use Yii;
use yii\base\Model;

class AddNewMessageToTicketForm extends Model
{
    /** @var string */
    public $type;

    /** @var int */
    public $message_id;

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [['type', 'message_id'], 'required'],
            [['message_id'], 'integer'],
            [['type'], 'string'],
        ];
    }

    /**
     * @return string[]
     */
    public function attributeLabels(): array
    {
        return [
            'type' => Yii::t('app', 'Тип сети'),
            'message_id' => Yii::t('app', 'ID сообщения'),
        ];
    }

    public function getDto(): AddNewMessageToTicketDTO
    {
        $addNewMessageToTicketDTO = new AddNewMessageToTicketDTO();

        $addNewMessageToTicketDTO->type = $this->type;
        $addNewMessageToTicketDTO->messageId = $this->message_id;

        return $addNewMessageToTicketDTO;
    }
}
