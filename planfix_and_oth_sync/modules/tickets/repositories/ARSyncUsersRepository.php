<?php

namespace app\modules\tickets\repositories;

use app\models\sync\SyncPlanfixCompanies;
use app\modules\tickets\repositories\contracts\SyncUsersRepositoryInterface;

class ARSyncUsersRepository implements SyncUsersRepositoryInterface
{
    public function getByCabinetUserId(int $id): SyncPlanfixCompanies
    {
        return SyncPlanfixCompanies::find()
            ->where([
                'or',
                ['leads_id' => $id],
                ['trade_id' => $id],
            ])
            ->one();
    }

    /**
     * @param int[] $ids
     * @return array[]
     */
    public function findAllByPlanfixIdsWithIndex(array $ids): array
    {
        return SyncPlanfixCompanies::find()
            ->where(['planfix_userid' => $ids])
            ->indexBy('planfix_userid')
            ->asArray()
            ->all();
    }
}
