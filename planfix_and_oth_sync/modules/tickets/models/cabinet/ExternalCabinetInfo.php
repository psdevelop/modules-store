<?php

namespace app\modules\tickets\models\cabinet;

use app\modules\tickets\models\BaseModel;

class ExternalCabinetInfo extends BaseModel
{
    /** @var string */
    public $login;

    /** @var string */
    public $networkTitle;

    /** @var string */
    public $status;

    /** @var string */
    public $billingType;

    /** @var string */
    public $name;

    /** @var string */
    public $lastName;

    /** @var string */
    public $middleName;

    /** @var string */
    public $phone;

    /** @var string */
    public $email;

    /** @var string */
    public $additional;
    
    /** @var int */
    public $id;

    /**
     * @param string[] $arr
     * @return ExternalCabinetInfo
     */
    public static function getInstanceFromArray(array $arr): ExternalCabinetInfo
    {
        return new self([
            'id' => $arr['id'] ?? null,
            'login' => $arr['login'] ?? null,
            'networkTitle' => $arr['network_title'] ?? null,
            'status' => $arr['status'] ?? null,
            'billingType' => $arr['billing_type'] ?? null,
            'name' => $arr['info']['name'] ?? null,
            'lastName' => $arr['info']['last_name'] ?? null,
            'middleName' => $arr['info']['middle_name'] ?? null,
            'phone' => $arr['info']['phone'] ?? null,
            'email' => $arr['info']['email'] ?? null,
            'additional' => $arr['info']['login'] ?? $arr['info']['vk_link'] ?? null,
        ]);
    }
}
