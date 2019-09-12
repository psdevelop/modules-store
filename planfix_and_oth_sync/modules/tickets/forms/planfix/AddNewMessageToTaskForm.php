<?php

namespace app\modules\tickets\forms\planfix;

use app\modules\tickets\dto\AddNewMessageToTaskDTO;
use Yii;
use yii\base\Model;

class AddNewMessageToTaskForm extends Model
{
    /** @var string */
    public $type;

    /** @var string */
    public $message_json;

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [['type', 'message_json'], 'required'],
            [['type', 'message_json'], 'string'],
        ];
    }

    /**
     * @return string[]
     */
    public function attributeLabels(): array
    {
        return [
            'type' => Yii::t('app', 'Тип сети'),
            'message_json' => Yii::t('app', 'Сообщение'),
        ];
    }

    public function getDto(): AddNewMessageToTaskDTO
    {
        $messageJson = json_decode($this->message_json);

        $addNewMessageToTaskDTO = new AddNewMessageToTaskDTO();

        $addNewMessageToTaskDTO->type = $this->type;
        $addNewMessageToTaskDTO->messageId = $messageJson->id;

        return $addNewMessageToTaskDTO;
    }
}
