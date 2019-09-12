<?php

namespace app\modules\tickets\repositories;

use app\modules\tickets\helpers\ApiPlanfix;
use app\modules\tickets\models\planfix\ExternalPlanfixTask;
use app\modules\tickets\repositories\contracts\PlanfixTaskRepositoryInterface;
use app\modules\tickets\repositories\exceptions\EntityNotFoundException;
use app\modules\tickets\repositories\exceptions\EntitySaveErrorException;
use Exception;

class ApiPlanfixTaskRepository implements PlanfixTaskRepositoryInterface
{
    const MAX_AMOUNT_TASKS_IN_QUERY = 100;

    /** @var ApiPlanfix */
    private $apiPlanfix;

    public function __construct(ApiPlanfix $apiPlanfix)
    {
        $this->apiPlanfix = $apiPlanfix;
    }

    public function save(ExternalPlanfixTask $externalPlanfixTask): int
    {
        try {
            $response = $this->apiPlanfix->sendRequest(
                ApiPlanfix::METHOD_TASK_ADD,
                $externalPlanfixTask->toArrayForRequest()
            );

            if (! $this->apiPlanfix->isResponseOk($response)) {
                throw new EntitySaveErrorException('ExternalPlanfixTask not saved');
            }

            return $response['task']['id'];
        } catch (Exception $exception) {
            throw new EntitySaveErrorException('ExternalPlanfixTask not saved');
        }
    }

    public function getById(int $id): ExternalPlanfixTask
    {
        $response = $this->apiPlanfix->sendRequest(
            ApiPlanfix::METHOD_TASK_GET,
            ['task' => ['id' => $id]]
        );

        if (! $this->apiPlanfix->isResponseOk($response)) {
            throw new EntityNotFoundException('ExternalPlanfixTask with id: ' . $id . ' not found');
        }

        return $this->taskResponseToExternalPlanfixTask($response['task']);
    }

    public function updateStatus(int $taskId, string $status)
    {
        $response = $this->apiPlanfix->sendRequest(
            ApiPlanfix::METHOD_TASK_UPDATE,
            [
                'task' => [
                    'id' => $taskId,
                    'status' => $status,
                ]
            ]
        );

        if (! $this->apiPlanfix->isResponseOk($response)) {
            throw new EntitySaveErrorException('ExternalPlanfixTask with id: ' . $taskId . ' not updated');
        }
    }

    /**
     * @param int[] $ids
     * @return ExternalPlanfixTask[]
     * @throws EntityNotFoundException
     */
    public function findByIds(array $ids): array
    {
        $chunksIds = array_chunk($ids, self::MAX_AMOUNT_TASKS_IN_QUERY);
        $result = [];

        foreach ($chunksIds as $chunkIds) {
            $requestIds['id'] = [];
            foreach ($chunkIds as $id) {
                $requestIds['id'][] = $id;
            }

            $response = $this->apiPlanfix->sendRequest(
                ApiPlanfix::METHOD_TASK_GET_MULTI,
                ['tasks' => $requestIds]
            );

            if (! $this->apiPlanfix->isResponseOk($response)) {
                throw new EntityNotFoundException('
                    ExternalPlanfixTask findByIds ids: ' . implode(',', $chunkIds) . ' not found'
                );
            }

            if (!empty($response['tasks']['task']['id'])) {
                $result[] = $this->taskResponseToExternalPlanfixTask($response['tasks']['task']);
            } else {
                foreach ($response['tasks']['task'] as $task) {
                    $result[] = $this->taskResponseToExternalPlanfixTask($task);
                }
            }
        }

        return $result;
    }

    private function taskResponseToExternalPlanfixTask(array $task): ExternalPlanfixTask
    {
        return new ExternalPlanfixTask([
            'id' => $task['id'],
            'general' => $task['general'],
            'template' => $task['template']['id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'importance' => $task['id'],
            'status' => $task['status'],
            'statusSet' => $task['statusSet'],
            'projectId' => $task['project']['id'],
            'beginDateTime' => $task['startTime'],
            'duration' => $task['duration'],
        ]);
    }
}
