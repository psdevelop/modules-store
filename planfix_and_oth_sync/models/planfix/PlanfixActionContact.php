<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 01.04.2017
 */

namespace app\models\planfix;

use app\models\cabinet\CabinetAccountNote;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixCompanies;

class PlanfixActionContact extends PlanfixBase
{
    public $objectName = 'action';
    public $objectKey = 'id';

    public $id;

    public $description;
    public $notifiedList;

    public $isHidden;
    public $owner;
    public $dateTime;

    public $fields = [
        'id',
        'description',
        'task',
        'notifiedList',
        'isHidden',
        'owner',
        'dateTime',
    ];

    /**
     * @param $note CabinetAccountNote
     * @param $params
     * @return array|null
     */
    public function getNoteContactId($note, $params)
    {
        if (!$cabinetClient = $note->client) {
            return null;
        }

        if (!$note->base) {
            return null;
        }

        /**
         * @var $syncClient SyncBase
         */
        if (!$syncClient = SyncPlanfixCompanies::findOne([$note->base . '_id' => $cabinetClient->id])) {
            return null;
        }

        if (!$planfixContactId = $syncClient->planfix_id) {
            return null;
        }

        $planfixId = $this->planfixWs->getTaskIdByContact($planfixContactId);
        if ($planfixId) {
            return [
                'id' => $planfixId
            ];
        }
        return null;
    }

    /**
     * @param $note CabinetAccountNote
     * @param $params
     * @return string
     */
    public function getNoteDescription($note, $params)
    {
        $noteText = nl2br($note->note);
        $noteText = preg_replace('/\\[glossary:(.*)]/is','',$noteText);
        return $noteText;
    }

    /**
     * @param $note CabinetAccountNote
     * @param $params
     * @return array|null
     */
    public function getNoteOwner($note, $params)
    {
        if (!$employee = $note->employee) {
            return null;
        }

        $employeeKey = $employee->email;
        $planfixUsers = PlanfixBase::instance()->planfixUsers;
        if (isset($planfixUsers[$employeeKey]['id'])) {
            return ['id' => $planfixUsers[$employeeKey]['id']];
        }

         return null;
    }

    /**
     * @param $note CabinetAccountNote
     * @param $format
     * @return false|string
     */
    public function getCreated($note, $format)
    {
        return date($format, strtotime($note->created));
    }
}
