<?php

namespace app\modules\tickets\forms\leads;

use app\modules\tickets\dto\AddHiddenMessageToTicketDTO;
use Yii;
use yii\base\Model;

/**
 * Class AddHiddenMessageToTicketForm
 * @package app\modules\tickets\forms\leads
 */
class AddHiddenMessageToTicketForm extends Model
{
    /** @var string */
    public $type;

    /** @var string */
    public $messageText;

    /** @var int */
    public $ticketId;

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [['type', 'messageText', 'ticketId'], 'required'],
            [['ticketId'], 'integer'],
            [['type', 'messageText'], 'string'],
        ];
    }

    /**
     * @return string[]
     */
    public function attributeLabels(): array
    {
        return [
            'type' => Yii::t('app', 'Тип сети'),
            'messageText' => Yii::t('app', 'Текст сообщения'),
            'ticketId' => Yii::t('app', 'ID тикета'),
        ];
    }

    /**
     * @return AddHiddenMessageToTicketDTO
     */
    public function getDto(): AddHiddenMessageToTicketDTO
    {
        $addHiddenMessageToTicketDTO = new AddHiddenMessageToTicketDTO();

        $addHiddenMessageToTicketDTO->type = $this->type;
        $addHiddenMessageToTicketDTO->messageText = $this->messageText;
        $addHiddenMessageToTicketDTO->ticketId = $this->ticketId;

        return $addHiddenMessageToTicketDTO;
    }
}
