<?php

namespace app\modules\tickets\repositories;

use app\modules\tickets\helpers\ApiPlanfix;
use app\modules\tickets\models\planfix\ExternalPlanfixTaskMessage;
use app\modules\tickets\repositories\contracts\PlanfixMessagesRepositoryInterface;
use app\modules\tickets\repositories\exceptions\EntityNotFoundException;
use app\modules\tickets\repositories\exceptions\EntitySaveErrorException;
use app\modules\tickets\repositories\exceptions\EntityUpdateErrorException;
use Exception;

class ApiPlanfixTaskMessageRepository implements PlanfixMessagesRepositoryInterface
{
    const MAX_AMOUNT_TASKS_IN_QUERY = 100;

    const NUM_FIRST_PAGE = 1;

    const TYPE_ACTION_DEFAULT = 'ACTION';
    const TYPE_ACTION_COMMENT = 'COMMENT';

    /** @var ApiPlanfix */
    private $apiPlanfix;

    public function __construct(ApiPlanfix $apiPlanfix)
    {
        $this->apiPlanfix = $apiPlanfix;
    }

    /**
     * @param ExternalPlanfixTaskMessage $externalPlanfixTaskMessage
     * @return int
     * @throws EntitySaveErrorException
     */
    public function save(ExternalPlanfixTaskMessage $externalPlanfixTaskMessage): int
    {
        try {
            $response = $this->apiPlanfix->sendRequest(
                ApiPlanfix::METHOD_ACTION_ADD,
                $externalPlanfixTaskMessage->toArrayForAddMessageRequest()
            );

            if (! $this->apiPlanfix->isResponseOk($response)) {
                throw new EntitySaveErrorException('ExternalPlanfixTaskMessage not saved');
            }

            return $response['action']['id'];
        } catch (Exception $exception) {
            throw new EntitySaveErrorException('ExternalPlanfixTaskMessage not saved');
        }
    }

    /**
     * @param ExternalPlanfixTaskMessage $externalPlanfixTaskMessage
     * @return int
     * @throws EntityUpdateErrorException
     */
    public function update(ExternalPlanfixTaskMessage $externalPlanfixTaskMessage): int
    {
        try {
            $response = $this->apiPlanfix->sendRequest(
                ApiPlanfix::METHOD_ACTION_UPDATE,
                $externalPlanfixTaskMessage->toArrayForUpdateMessageRequest()
            );

            if (!$this->apiPlanfix->isResponseOk($response)) {
                throw new EntityUpdateErrorException('ExternalPlanfixTaskMessage not updated');
            }

            return $response['action']['id'];
        } catch (Exception $exception) {
            throw new EntityUpdateErrorException('ExternalPlanfixTaskMessage not updated');
        }
    }


    /**
     * @param int $taskId
     * @return array
     * @throws EntityNotFoundException
     */
    public function getAllByTaskIdOrderAsc(int $taskId): array
    {
        try {
            $response = $this->apiPlanfix->sendRequest(
                ApiPlanfix::METHOD_ACTION_GET_LIST,
                [
                    'task' => ['id' => $taskId],
                    'pageCurrent' => self::NUM_FIRST_PAGE,
                    'pageSize' => self::MAX_AMOUNT_TASKS_IN_QUERY,
                    'sort' => 'asc',
                ]
            );

            if (! $this->apiPlanfix->isResponseOk($response)) {
                return [];
            }

            $result = $this->responseToArrayExternalPlanfixTaskMessages($response);
            $totalCountMessages = $response['actions']['@attributes']['totalCount'];

            if ($response['actions']['@attributes']['count'] === $totalCountMessages) {
                return $result;
            }

            $amountAdditionalQueries = ceil($totalCountMessages / self::MAX_AMOUNT_TASKS_IN_QUERY) - 1;

            for ($numPage = self::NUM_FIRST_PAGE; $numPage <= $amountAdditionalQueries; $numPage++) {
                $response = $this->apiPlanfix->sendRequest(
                    ApiPlanfix::METHOD_ACTION_GET_LIST,
                    [
                        'task' => ['id' => $taskId],
                        'pageCurrent' => $numPage + 1,
                        'pageSize' => self::MAX_AMOUNT_TASKS_IN_QUERY,
                        'sort' => 'asc',
                    ]
                );

                if (! $this->apiPlanfix->isResponseOk($response)) {
                    continue;
                }

                $pageResult = $this->responseToArrayExternalPlanfixTaskMessages($response);
                $result = array_merge($result, $pageResult);
            }

            return $result;
        } catch (Exception $exception) {
            throw new EntityNotFoundException('ExternalPlanfixTaskMessage find by taskId:' . $taskId . ' - error');
        }
    }

    /**
     * @param int $messageId
     * @return ExternalPlanfixTaskMessage
     * @throws EntityNotFoundException
     */
    public function getById(int $messageId): ExternalPlanfixTaskMessage
    {
        try {
            $response = $this->apiPlanfix->sendRequest(
                ApiPlanfix::METHOD_ACTION_GET,
                [
                    'action' => ['id' => $messageId],
                ]
            );

            if (! $this->apiPlanfix->isResponseOk($response) || ! $this->isComment($response['action']['type'])) {
                throw new EntitySaveErrorException('ExternalPlanfixTaskMessage getById:' . $messageId . ' has error');
            }

            return $this->arrToExternalPlanfixTaskMessage($response['action']);
        } catch (Exception $exception) {
            throw new EntityNotFoundException('ExternalPlanfixTaskMessage getById:' . $messageId . ' has error');
        }
    }

    /**
     * @param array $response
     * @return array
     */
    private function responseToArrayExternalPlanfixTaskMessages(array $response): array
    {
        $result = [];

        foreach ($response['actions']['action'] as $action) {
            if (! $this->isComment($action['type'])) {
                continue;
            }

            $result[] = $this->arrToExternalPlanfixTaskMessage($action);
        }

        return $result;
    }

    /**
     * @param array $arr
     * @return ExternalPlanfixTaskMessage
     */
    private function arrToExternalPlanfixTaskMessage(array $arr): ExternalPlanfixTaskMessage
    {
        $notifiedList = [];

        if (count($arr['notifiedList'])) {
            foreach ($arr['notifiedList'] as $user) {
                if (array_key_exists('id', $user)) {
                    $notifiedList[] = $user['id'];
                } else {
                    foreach ($user as $item) {
                        $notifiedList[] = $item['id'];
                    }
                }
            }
        }

        return new ExternalPlanfixTaskMessage([
            'id' => $arr['id'],
            'description' => $arr['description'],
            'taskId' => $arr['task']['id'],
            'notifiedListUserIds' => $notifiedList,
        ]);
    }

    /**
     * @param string $type
     * @return bool
     */
    private function isComment(string $type): bool
    {
        $availableTypes = [self::TYPE_ACTION_COMMENT, self::TYPE_ACTION_DEFAULT];

        return in_array($type, $availableTypes);
    }
}
