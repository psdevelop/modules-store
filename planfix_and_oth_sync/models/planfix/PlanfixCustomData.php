<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 31.03.2017
 */

namespace app\models\planfix;


use app\components\enums\AchievementTypesEnum;
use app\models\cabinet\CabinetAchievements;
use app\models\cabinet\CabinetAdvertiser;
use app\models\cabinet\CabinetAffiliate;
use app\models\cabinet\CabinetBase;
use app\models\cabinet\CabinetCompany;
use app\models\cabinet\CabinetCompanyUser;
use app\models\cabinet\CabinetEmployee;
use app\models\cabinet\CabinetOffer;

class PlanfixCustomData extends PlanfixBase
{
    const FIELD_DATE_CREATE = 'date_create';
    const FIELD_EXT_ID = 'ext_id';

    /**
     * @var
     */
    public $statusMap;

    /**
     * @var array
     */
    public $customMap;

    /**
     * Рендер всех кастомных полей по маппингу
     * @param $object
     * @param $map
     * @return array
     */
    public function prepareCustomFields($object, $map)
    {
        $fields = $this->toPlanfix($object, $map);
        $outputObject = [];
        foreach ($fields as $fieldKey => $fieldValue) {
            if (is_array($fieldValue)) {
                foreach ($fieldValue as $item) {
                    if ($increment = $this->prepareField($fieldKey, $item)) {
                        $outputObject[] = ['customValue' => $increment];
                    }
                }
                continue;
            }

            if ($increment = $this->prepareField($fieldKey, $fieldValue)) {
                $outputObject[] = ['customValue' => $increment];
            }
        }
        return $outputObject;
    }

    /**
     * Рендер кастомного поля
     * @param $key
     * @param $value
     * @return array|null
     */
    public function prepareField($key, $value)
    {
        if (!isset($this->customFields[$key])) {
            return null;
        }
        $fieldId = $this->customFields[$key];
        return [
            'id' => $fieldId,
            'value' => $value,
        ];

    }

    /**
     * Дата создания в формате Planfix
     * @param $object
     * @return bool|null|string
     */
    public function getDateAdded($object)
    {
        if (isset($object->{$object::$createdField}) && $object->{$object::$createdField}) {
            return date('d-m-Y H:i', strtotime($object->{$object::$createdField}));
        }
        return null;
    }

    /**
     * Дата обновления в формате Planfix
     * @param $object CabinetBase
     * @return bool|null|string
     */
    public function getDateModified($object)
    {
        if (isset($object->{$object::$modifiedField}) && $object->{$object::$modifiedField}) {
            return date('d-m-Y H:i', strtotime($object->{$object::$modifiedField}));
        }
        return null;
    }


    /**
     * @param $object CabinetBase
     * @param $value
     * @return mixed
     */
    public function getNetworks($object, $value)
    {
        $networksOut = [];
        if ($object->leads_id) {
            $networksOut[] = 'Leads';
        }
        if ($object->trade_id) {
            $networksOut[] = 'TradeLeads';
        }
        return json_encode($networksOut);
    }

    /**
     * @param $object CabinetOffer
     * @param $value
     * @return mixed
     */
    public function getProjectNetwork($object, $value)
    {
        $networksOut = [];
        if ($object->leads_id) {
            $networksOut[] = 'Leads';
        }
        if ($object->trade_id) {
            $networksOut[] = 'TradeLeads';
        }
        return json_encode($networksOut);
    }

    /**
     * @param $object CabinetOffer
     * @param $value
     * @return mixed
     */
    public function getCabinetOfferUrl($object, $value)
    {
        return sprintf("http://%s/offers/default/view/%d", $this->cabinets[$object->base][$value],$object->id);
    }

    /**
     * @param $object CabinetEmployee
     * @param $value
     * @return mixed
     */
    public function getCabinetEmployeeUrl($object, $value)
    {
        return sprintf("http://%s/employees/default/view/%d", $this->cabinets[$object->base][$value],$object->id);
    }

    /**
     * @param $object CabinetBase
     * @param $mask
     * @return null
     */
    public function getMaskId($object, $mask)
    {
        $propertyKey = $mask[1] . '_' . $mask[0] . '_id';
        return isset($object->{$propertyKey}) ? $object->{$propertyKey} : null;
    }

    /**
     * @param $object
     * @return null
     */
    public function getRuStatus($object)
    {
        $objectStatus = strtolower($object->status);
        return isset($this->statusMap[$objectStatus]) ? $this->statusMap[$objectStatus] : null;
    }

    /**
     *
     * @param $object CabinetCompany | CabinetCompanyUser
     * @param $mask
     * @return string
     */
    public function getCabinetOwnerString($object, $mask)
    {
        /**
         * @var $employee CabinetEmployee
         */
        if (!($employee = $object->employee ?? null) || !($object->employee instanceof CabinetEmployee)) {
            return "-";
        }

        $employeeFullName = $employee->first_name . ' ' . $employee->last_name;
        $employeeEmail = $employee->email;
        print_r(sprintf($mask, $employeeFullName, $employeeEmail));
        print "\n";
        return sprintf($mask, $employeeFullName, $employeeEmail);
    }

    /**
     * Кастомны статус проекта из оффера
     * @param $object CabinetOffer
     * @return string
     */
    public function getCabinetOfferStatus($object)
    {
        return $object->getRuStatus();
    }

    /**
     * @param CabinetAchievements $object
     * @return mixed
     */
    public function getAchieveAmount($object)
    {
        return PlanfixAchievementTask::instance()->amount;
    }

    /**
     * @param CabinetAchievements $object
     * @return mixed
     */
    public function getAchieveType($object)
    {
        return AchievementTypesEnum::getClientValue($object->achieve_code);
//        return $object->achieve_code;
    }

    /**
     * @param CabinetBase $object
     * @return mixed
     */
    public function getId($object)
    {
        return $object->id ?? null;
    }
}
