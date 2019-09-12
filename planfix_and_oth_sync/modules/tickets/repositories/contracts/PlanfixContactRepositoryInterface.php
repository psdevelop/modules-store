<?php

namespace app\modules\tickets\repositories\contracts;

use app\modules\tickets\models\planfix\ExternalPlanfixContact;

/**
 * Interface PlanfixContactRepositoryInterface
 * @package app\modules\tickets\repositories\contracts
 */
interface PlanfixContactRepositoryInterface
{
    /**
     * @param int $planfixGeneralId
     * @return ExternalPlanfixContact
     */
    public function getByPlanfixGeneralId(int $planfixGeneralId): ExternalPlanfixContact;
}
