<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 29.03.2017
 */

namespace app\models\planfix;


use app\components\helpers\TimerHelper;
use app\models\cabinet\CabinetBase;

/**
 * Class PlanfixContact
 * @package app\models\planfix
 */
class PlanfixContact extends PlanfixBase
{
    public $objectName = 'contact';
    public $objectKey = 'id';

    public $id;

    public $template;
    public $name;
    public $lastName;
    public $post;
    public $email;
    public $mobilePhone;
    public $workPhone;
    public $homePhone;
    public $address;
    public $description;
    public $sex;
    public $skype;
    public $icq;
    public $birthdate;
    public $lang;
    public $isCompany;
    public $canBeWorker;
    public $canBeClient;
    public $group;
    public $customData;
    public $customValue;
    public $userPic;
    public $havePlanfixAccess;
    public $contractors;

    public $advertiserCompanyMap;

    /**
     * Поля объекта по спецификации Planfix
     * @var array
     */
    public $fields = [
        'id',
        'general',
        'template',
        'name',
        'userid',
        'lastName',
        'post',
        'email',
        'mobilePhone',
        'workPhone',
        'homePhone',
        'address',
        'description',
        'sex',
        'skype',
        'icq',
        'birthdate',
        'lang',
        'user',
        'isCompany',
        'canBeWorker',
        'canBeClient',
        'group',
        'customData',
        'userPic',
        'havePlanfixAccess',
        'contractors'
    ];

    /**
     * @param $object CabinetBase
     * @param $groupType
     * @return array
     */
    public function getGroup($object, $groupType)
    {
        if (!$groupType) {
            return null;
        }

        if (!$this->groups) {
            return null;
        }

        if (!isset($this->groups[$groupType])) {
            return null;
        }

        $groupId = $this->groups[$groupType];

        return ['id' => $groupId];
    }

    /**
     * @param CabinetBase $object
     * @param $email
     * @return array
     */
    public function getGeneratedEmail($object, $email)
    {
        $generatedEmail = '';

        if ($object->leads_id) {
            $generatedEmail = 'leadsfakemail_' . $object->leads_id . '_@leadsfakemail.su';
        }

        if ($object->trade_id) {
            $generatedEmail = 'lbfakemail_' . $object->trade_id . '_@leadsfakemail.su';
        }

        return $generatedEmail;
    }

    /**
     * @param $contactId
     * @param $contractorIds
     * @return array
     */
    public function updateContractors($contactId, $contractorIds)
    {
        TimerHelper::timerRun();
        $planfixContact = self::findOne([
            'id' => $contactId
        ]);
        TimerHelper::timerStop(null, "Update contactors. Find PF contact", "PF_API");

        TimerHelper::timerRun();
        $planfixContactContractors = isset($planfixContact['contractors']['client']) ? $planfixContact['contractors']['client'] : [];
        foreach ($planfixContactContractors as &$planfixContactContractor) {
            if (isset($planfixContactContractor['id']) && in_array($planfixContactContractor['id'], $contractorIds)) {
                unset($planfixContactContractor);
            }
        }
        TimerHelper::timerStop(null, "Update contactors. Reset old contractors", "php");

        TimerHelper::timerRun();
        $updateRequest = [
            'id' => $contactId,
            'contractors' => [
                'addClient' => [],
                'delClient' => $planfixContactContractors
            ]
        ];

        if (empty($contractorIds)) {
            TimerHelper::timerStop(null, "Update contactors. Prepare request - fail", "php");
            return [];
        }

        foreach ($contractorIds as $contractorId) {
            $updateRequest['contractors']['addClient'][] = ['id' => $contractorId];
        }
        TimerHelper::timerStop(null, "Update contactors. Prepare request", "php");

        TimerHelper::timerRun();
        $this->planfixApi->api(
            $this->objectName . '.updateContractors',
            [
                $this->objectName => $updateRequest
            ],
            true
        );
        TimerHelper::timerStop(null, "Update contactors. PF query update", "PF_API");

        return $updateRequest;
    }

    /**
     * Поиск по email
     * @param string $mail
     * @param bool $strongEqual
     * @return array|mixed
     */
    public static function findByEmail(string $mail, $strongEqual = true)
    {
        $result = static::find([
            'filters' => [
                [
                    'filter' => [
                        'type' => 4005,
                        'operator' => 'equal',
                        'value' => $mail
                    ]
                ]
            ]
        ]);

        if ($strongEqual) {
            foreach ($result as $id => $object) {
                if (!isset($object['email'])) {
                    unset($result[$id]);
                    continue;
                }
                if (strcasecmp($object['email'], $mail) !== 0) {
                    unset($result[$id]);
                    continue;
                }
            }
        }
        return $result;
    }

    /**
     * Шаблон контакта из конфига
     * @param $object CabinetBase
     * @param $contactType
     * @return null
     */
    public function getTemplate($object, $contactType)
    {
        return $this->config['templates'][$contactType] ?? null;
    }

    /**
     * Префикс Л/Т
     * @param $object CabinetBase
     * @param $params
     * @return null
     */
    public function getNamePrefix($object, $params)
    {
        return $object->getBasePrefix();
    }
}
