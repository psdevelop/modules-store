<?php

namespace app\modules\tickets\repositories\contracts;

use app\modules\tickets\models\planfix\ExternalPlanfixTask;

interface PlanfixTaskRepositoryInterface
{
    public function getById(int $id): ExternalPlanfixTask;

    /**
     * @param int[] $ids
     * @return ExternalPlanfixTask[]
     */
    public function findByIds(array $ids): array;

    public function save(ExternalPlanfixTask $externalPlanfixTask): int;

    public function updateStatus(int $taskId, string $status);
}
