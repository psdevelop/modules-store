<?php

namespace app\modules\tickets\models\cabinet;

use app\modules\tickets\enum\TicketAccountTypeEnum;
use app\modules\tickets\models\BaseModel;
use DateTimeImmutable;

class ExternalTicket extends BaseModel
{
    /** @var int */
    public $id;

    /** @var int */
    public $employeeId;

    /** @var int */
    public $planfixTaskId;

    /** @var string */
    public $project;

    /** @var int */
    public $category;

    /** @var int */
    public $subcategory;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var int */
    public $accountId;

    /** @var string */
    public $accountType;

    /** @var string */
    public $accountCompany;

    /** @var string */
    public $status;

    /** @var DateTimeImmutable */
    public $created;

    /** @var DateTimeImmutable */
    public $modified;

    /** @var ExternalTicketInfo */
    public $additionalInformation;

    /** @var int */
    public $managerId;

    public function getLinkAccount(string $domainUrlAccountWebmaster, string $domainUrlAccountAdvertise): string
    {
        if ($this->accountType === TicketAccountTypeEnum::TYPE_AFFILIATE) {
            return $domainUrlAccountWebmaster . $this->accountId;
        }

        return $domainUrlAccountAdvertise . $this->accountId;
    }

    public function getLinkOnTicket(string $domainUrl): string
    {
        return $domainUrl . $this->id;
    }
}
