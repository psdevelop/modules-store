<?php

namespace app\modules\tickets\models\planfix;

use app\modules\tickets\models\BaseModel;

/**
 * Class ExternalPlanfixContact
 * @package app\modules\tickets\models\planfix
 */
class ExternalPlanfixContact extends BaseModel
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $generalId;
    /**
     * @var int
     */
    public $userId;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $midName;
    /**
     * @var string
     */
    public $lastName;
    /**
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $description;
    /**
     * @var bool
     */
    public $isCompany;
    /**
     * @var int[]
     */
    public $responsibleUsers;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['generalId', 'userId', 'name', 'responsibleUsers'], 'required'],
        ];
    }

    /**
     * @return null|int
     */
    public function getResponsibleUserId()
    {
        return empty($this->responsibleUsers[0]) ? null : $this->responsibleUsers[0];
    }
}
