<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 01.04.2017
 */

namespace app\models\planfix;


use app\models\cabinet\CabinetChatMessage;

class PlanfixActionTask extends PlanfixBase
{
    public $objectName = 'action';
    public $objectKey = 'id';

    public $id;

    public $description;
    public $taskNewStatus;
    public $notifiedList;
    public $statusChange;
    public $isHidden;
    public $owner;
    public $dateTime;
    public $analitics;
    public $analitic;
    public $analiticData;
    public $itemData;
    public $type;

    public $fields = [
        'type',
        'statusChange',
        'description',
        'taskNewStatus',
        'task',
        'notifiedList',
        'isHidden',
        'owner',
        'dateTime',
    ];

    /**
     * @param $message CabinetChatMessage
     * @param $params
     * @return mixed
     */
    public function getActionTaskGeneral($message, $params)
    {
        $planfixChatGeneral = isset($message->planfixTaskGeneral) ? $message->planfixTaskGeneral : null;
        if ($planfixChatGeneral) {
            return [
                'general' => $planfixChatGeneral
            ];
        }
        return null;
    }

    /**
     * @param $message CabinetChatMessage
     * @param $params
     * @return mixed
     */
    public function getActionTaskId($message, $params)
    {
        $planfixChatGeneral = isset($message->planfixTaskId) ? $message->planfixTaskId : null;
        if ($planfixChatGeneral) {
            return [
                'id' => $planfixChatGeneral
            ];
        }
        return null;
    }

    /**
     * @param $message CabinetChatMessage
     * @param $params
     * @return null
     */
    public function getActionWorkers($message, $params)
    {
        return isset($message->planfixChatWorkers[$message->message_type]) ? $message->planfixChatWorkers[$message->message_type] : null;
    }

    /**
     * @param $message CabinetChatMessage
     * @param $params
     * @return mixed
     */
    public function getMessageContent($message, $params)
    {
        $type = $message->message_type;
        switch ($type) {
            case 'agent':
                return '<div style="text-align: right;"><em>' . $message->prepareMessage() . '</em></div>';
                break;
            case 'client':
                return '<div style="text-align: left;"><em>' . $message->prepareMessage() . '</em></div>';
                break;
        }

        return $message->message;
    }

    /**
     * @param $message CabinetChatMessage
     * @param $format
     * @return false|string
     */
    public function getCreated($message, $format)
    {
        return date($format, strtotime($message->created));
    }

    /**
     * @param $message
     * @param $uid
     * @return array
     */
    public function getChatUser($message, $uid)
    {
        return ['id' => $uid];
    }

    /**
     * Последний экшн, меняющий статус
     * @param $planfixId
     * @return mixed
     */
    public static function getStatusChangeAction($planfixId)
    {
        $planfixRequestStatusActions = self::getStatusChangeActions($planfixId);
        return $planfixRequestStatusActions ? array_shift($planfixRequestStatusActions) : null;
    }


    public static function getStatusChangeActions($planfixId)
    {
        $planfixRequestActions = PlanfixActionTask::find([
            'task' => [
                'id' => $planfixId
            ]
        ]);

        return array_filter($planfixRequestActions, function ($action) {
            return isset($action['statusChange']['newStatus']);
        });
    }
}
