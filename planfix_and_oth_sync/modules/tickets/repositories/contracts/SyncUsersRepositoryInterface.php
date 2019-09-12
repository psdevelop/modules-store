<?php

namespace app\modules\tickets\repositories\contracts;

use app\models\sync\SyncPlanfixCompanies;

interface SyncUsersRepositoryInterface
{
    public function getByCabinetUserId(int $id): SyncPlanfixCompanies;

    /**
     * @param int[] $ids
     * @return array[]
     */
    public function findAllByPlanfixIdsWithIndex(array $ids): array;
}
