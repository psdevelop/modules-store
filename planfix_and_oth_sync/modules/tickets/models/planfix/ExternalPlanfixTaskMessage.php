<?php

namespace app\modules\tickets\models\planfix;

use app\modules\tickets\helpers\CorrectTimeHelper;
use app\modules\tickets\models\BaseModel;
use yii\helpers\Html;

class ExternalPlanfixTaskMessage extends BaseModel
{
    /** @var int */
    public $id;

    /** @var string */
    public $description;

    /** @var string */
    public $taskId;

    /** @var int[] */
    public $notifiedListUserIds;

    /** @var int */
    public $ownerId;

    /** @var string */
    public $dateTime;

    /**
     * @return string[][]
     */
    public function toArrayForAddMessageRequest(): array
    {
        return [
            'action' => [
                'description' => self::normalizeMessage($this->description),
                'task' => ['id' => $this->taskId],
                'owner' => ['id' => $this->ownerId],
                'dateTime' => CorrectTimeHelper::correctDefaultDateTime($this->dateTime)->format('d-m-Y H:i'),
                'notifiedList' => ['user' => ['id' => $this->notifiedListUserIds]],
            ]
        ];
    }

    /**
     * @return array
     */
    public function toArrayForUpdateMessageRequest(): array
    {
        return [
            'action' => [
                'id' => $this->id,
                'description' => self::normalizeMessage($this->description),
            ]
        ];
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return md5(
            $this->description .
            json_encode($this->notifiedListUserIds)
        );
    }

    /**
     * @param string $string
     * @return string
     */
    private static function normalizeMessage(string $string): string
    {
        $string = Html::encode($string);
        $string = nl2br($string);

        return $string;
    }
}
