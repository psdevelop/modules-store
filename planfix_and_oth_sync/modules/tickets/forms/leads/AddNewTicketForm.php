<?php

namespace app\modules\tickets\forms\leads;

use app\modules\tickets\dto\AddNewMessageToTicketDTO;
use app\modules\tickets\dto\AddNewTicketDTO;
use Yii;
use yii\base\Model;

class AddNewTicketForm extends Model
{
    /** @var string */
    public $type;

    /** @var int */
    public $ticket_id;

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [['type', 'ticket_id'], 'required'],
            [['ticket_id'], 'integer'],
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
            'ticket_id' => Yii::t('app', 'ID тикета'),
        ];
    }

    public function getDto(): AddNewTicketDTO
    {
        $addNewTicketDTO = new AddNewTicketDTO();

        $addNewTicketDTO->type = $this->type;
        $addNewTicketDTO->ticketId = $this->ticket_id;

        return $addNewTicketDTO;
    }
}
