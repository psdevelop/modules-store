<?php

namespace app\modules\tickets\models\planfix;

use app\modules\tickets\helpers\CorrectTimeHelper;
use app\modules\tickets\helpers\MapperTicketPlanfix;
use app\modules\tickets\helpers\RenderTicketToTask;
use app\modules\tickets\models\BaseModel;
use app\modules\tickets\models\cabinet\ExternalTicket;
use yii\helpers\ArrayHelper;

class ExternalPlanfixTask extends BaseModel
{
    /** @var int */
    public $id;

    /** @var int */
    public $general;

    /** @var integer */
    public $template;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var string */
    public $importance;

    /** @var int */
    public $status;

    /** @var int */
    public $statusSet;

    /** @var int */
    public $projectId;

    /** @var int */
    public $clientId;

    /** @var string */
    public $beginDateTime;

    /** @var string */
    public $startDateTime;

    /** @var int */
    public $duration;

    /** @var int */
    public $ownerId;

    /** @var int[] */
    public $membersIds;

    /** @var int[] */
    public $workersUserIds;

    /** @var int[] */
    public $workersGroupIds;

    /**
     * @return string[][]
     */
    public function toArrayForRequest(): array
    {
        $requestArray = [
            'task' => [
                'template' => $this->template,
                'title' => $this->title,
                'description' => $this->description,
                'importance' => $this->importance,
                'status' => $this->status,
                'statusSet' => $this->statusSet,
                'project' => ['id' => $this->projectId],
                'client' => ['id' => $this->clientId],
                'owner' => ['id' => $this->ownerId],
                'members' => ['users' => ['id' => $this->membersIds]],
                'beginDateTime' => CorrectTimeHelper::correctDefaultDateTime($this->beginDateTime)->format('d-m-Y H:i'),
                'startDateIsSet' => '1',
                'startDate' => CorrectTimeHelper::correctDefaultDateTime($this->startDateTime)->format('d-m-Y'),
                'startTimeIsSet' => '1',
                'startTime' => CorrectTimeHelper::correctDefaultDateTime($this->startDateTime)->format('H:i'),
                'durationIsSet' => '1',
                'duration' => $this->duration,
                'durationUnit' => env('PLANFIX_DURATION_UNIT_DAY'),
            ]
        ];

        if (!empty($this->workersUserIds)) {
            $requestArray['task']['workers'] = ['users' => ['id' => $this->workersUserIds]];
        }
        if (!empty($this->workersGroupIds)) {
            $requestArray['task']['workers'] = ['groups' => ['id' => $this->workersGroupIds]];
        }

        return $requestArray;
    }

    /**
     * @return string
     */
    public function getHashForSync(): string
    {
        $arrForHash = [
            'title' => $this->title,
            'status' => $this->status,
            'statusSet' => $this->statusSet,
        ];

        return md5(json_encode($arrForHash));
    }

    /**
     * @param ExternalTicket $externalTicket
     * @param int $clientId
     * @param int $ownerId
     * @param int[] $members
     * @param int[] $workerUsers
     * @param int[] $workerGroups
     * @return ExternalPlanfixTask
     */
    public static function getInstanceByExternalCabinetTicket(
        ExternalTicket $externalTicket,
        int $clientId,
        int $ownerId,
        array $members = [],
        array $workerUsers = [],
        array $workerGroups = []
    ): self {
        return new self([
            'template' => MapperTicketPlanfix::getIdTaskTemplate(
                $externalTicket->project,
                $externalTicket->category,
                $externalTicket->subcategory
            ),
            'title' => RenderTicketToTask::renderTitle($externalTicket),
            'description' => RenderTicketToTask::renderDescription($externalTicket),
            'importance' => 'AVERAGE',
            'status' => MapperTicketPlanfix::getStatusTask($externalTicket->status),
            'statusSet' => MapperTicketPlanfix::getIdProcessTemplate(
                $externalTicket->project,
                $externalTicket->category,
                $externalTicket->subcategory
            ),
            'projectId' => MapperTicketPlanfix::getProjectIdByTitle($externalTicket->project),
            'beginDateTime' => $externalTicket->created,
            'startDateTime' => $externalTicket->created,
            'duration' => env('PLANFIX_DURATION_TASK_DAYS'),
            'clientId' => $clientId,
            'membersIds' => ArrayHelper::merge([$ownerId], $members),
            'workerUserIds' => $workerUsers,
            'workerGroupIds' => $workerGroups,
            'ownerId' => $ownerId,
        ]);
    }
}
