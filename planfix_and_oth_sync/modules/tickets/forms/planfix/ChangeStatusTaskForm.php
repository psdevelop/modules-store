<?php

namespace app\modules\tickets\forms\planfix;

use app\modules\tickets\dto\ChangeStatusTaskDTO;
use app\modules\tickets\services\EnvironmentService;
use Yii;
use yii\base\Model;

class ChangeStatusTaskForm extends Model
{
    /** @var string */
    public $type;

    /** @var int */
    public $task_id;

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [['type', 'task_id'], 'required'],
            [['task_id'], 'integer'],
            [['type'], 'string'],
            ['type', 'in', 'range' => [EnvironmentService::PROJECT_LEADS, EnvironmentService::PROJECT_BLACK]],
        ];
    }

    /**
     * @return string[]
     */
    public function attributeLabels(): array
    {
        return [
            'type' => Yii::t('app', 'Тип сети'),
            'task_id' => Yii::t('app', 'ID задачи'),
        ];
    }

    public function getDto(): ChangeStatusTaskDTO
    {
        $changeTaskDTO = new ChangeStatusTaskDTO();

        $changeTaskDTO->type = $this->type;
        $changeTaskDTO->taskId = $this->task_id;

        return $changeTaskDTO;
    }
}
