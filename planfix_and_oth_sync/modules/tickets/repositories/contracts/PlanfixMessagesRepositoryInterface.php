<?php

namespace app\modules\tickets\repositories\contracts;

use app\modules\tickets\models\planfix\ExternalPlanfixTaskMessage;

interface PlanfixMessagesRepositoryInterface
{
    /**
     * @param ExternalPlanfixTaskMessage $externalPlanfixTaskMessage
     * @return int
     */
    public function save(ExternalPlanfixTaskMessage $externalPlanfixTaskMessage): int;

    /**
     * @param ExternalPlanfixTaskMessage $externalPlanfixTaskMessage
     * @return int
     */
    public function update(ExternalPlanfixTaskMessage $externalPlanfixTaskMessage): int;

    /**
     * @param int $taskId
     * @return ExternalPlanfixTaskMessage[]
     */
    public function getAllByTaskIdOrderAsc(int $taskId): array;

    /**
     * @param int $messageId
     * @return ExternalPlanfixTaskMessage
     */
    public function getById(int $messageId): ExternalPlanfixTaskMessage;
}
