<?php

namespace app\modules\tickets\forms\leads;

use app\modules\tickets\dto\UpdateMessageInTicketDTO;
use Yii;
use yii\base\Model;

class UpdateMessageInTicketForm extends Model
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

    public function getDto(): UpdateMessageInTicketDTO
    {
        $updateMessageInTicketDTO = new UpdateMessageInTicketDTO();

        $updateMessageInTicketDTO->type = $this->type;
        $updateMessageInTicketDTO->messageId = $this->message_id;

        return $updateMessageInTicketDTO;
    }
}
