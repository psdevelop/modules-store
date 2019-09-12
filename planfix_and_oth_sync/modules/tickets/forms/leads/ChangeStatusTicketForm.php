<?php

namespace app\modules\tickets\forms\leads;

use app\modules\tickets\dto\ChangeStatusTicketDTO;
use app\modules\tickets\enum\TicketStatusEnum;
use app\modules\tickets\services\EnvironmentService;
use Yii;
use yii\base\Model;

class ChangeStatusTicketForm extends Model
{
    /** @var string */
    public $type;

    /** @var int */
    public $ticket_id;

    /** @var string */
    public $status;

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [['type', 'ticket_id', 'status'], 'required'],
            [['ticket_id'], 'integer'],
            [['type', 'status'], 'string'],
            ['type', 'in', 'range' => [EnvironmentService::PROJECT_LEADS, EnvironmentService::PROJECT_BLACK]],
            ['status', 'in', 'range' => TicketStatusEnum::getAll()],
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
            'status' => Yii::t('app', 'Статус'),
        ];
    }

    public function getDto(): ChangeStatusTicketDTO
    {
        $changeStatusDTO = new ChangeStatusTicketDTO();

        $changeStatusDTO->type = $this->type;
        $changeStatusDTO->ticketId = $this->ticket_id;
        $changeStatusDTO->status = $this->status;

        return $changeStatusDTO;
    }
}
