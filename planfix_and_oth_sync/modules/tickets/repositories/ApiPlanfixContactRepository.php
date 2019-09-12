<?php

namespace app\modules\tickets\repositories;

use app\modules\tickets\helpers\ApiPlanfix;
use app\modules\tickets\models\planfix\ExternalPlanfixContact;
use app\modules\tickets\repositories\contracts\PlanfixContactRepositoryInterface;
use app\modules\tickets\repositories\exceptions\EntityNotFoundException;
use Yii;

/**
 * Class ApiPlanfixContactRepository
 * @package app\modules\tickets\repositories
 */
class ApiPlanfixContactRepository implements PlanfixContactRepositoryInterface
{
    /** @var ApiPlanfix */
    private $apiPlanfix;

    public function __construct(ApiPlanfix $apiPlanfix)
    {
        $this->apiPlanfix = $apiPlanfix;
    }

    /**
     * @param int $planfixGeneralId
     * @return ExternalPlanfixContact
     * @throws EntityNotFoundException
     */
    public function getByPlanfixGeneralId(int $planfixGeneralId): ExternalPlanfixContact
    {
        $response = $this->apiPlanfix->sendRequest(
            ApiPlanfix::METHOD_CONTACT_GET,
            ['contact' => ['general' => $planfixGeneralId]]
        );

        if (!$this->apiPlanfix->isResponseOk($response)) {
            throw new EntityNotFoundException('ExternalPlanfixContact with general_id: ' . $planfixGeneralId . ' not found');
        }

        return $this->contactResponseToExternalPlanfixContact($response['contact']);
    }

    /**
     * @param array $planfixContact
     * @return ExternalPlanfixContact
     */
    private function contactResponseToExternalPlanfixContact(array $planfixContact): ExternalPlanfixContact
    {
        return new ExternalPlanfixContact([
            'id' => $planfixContact['id'],
            'generalId' => $planfixContact['general'],
            'userId' => $planfixContact['userid'],
            'name' => $planfixContact['name'],
            'email' => $planfixContact['email'],
            'isCompany' => (bool) $planfixContact['isCompany'],
            'responsibleUsers' => empty($planfixContact['responsible']['users']['user']['id']) ? [] : [$planfixContact['responsible']['users']['user']['id']],
        ]);
    }
}
