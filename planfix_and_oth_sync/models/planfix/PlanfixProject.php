<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 05.09.17
 * Time: 11:02
 */

namespace app\models\planfix;
use app\models\cabinet\CabinetBase;

/**
 * Class PlanfixProject
 * @property array $projectGroups
 * @property array $projectsOffers
 *
 * @package app\models\planfix
 */
class PlanfixProject extends PlanfixBase
{
    public $objectName = 'project';
    public $objectKey = 'id';

    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_DRAFT = 'DRAFT';
    const STATUS_COMPLETED = 'COMPLETED';

    public $id;
    /**
     * @var string Название проекта
     **/
    public $title;
    /**
     * @var string Описание проекта которое задает пользователь
     **/
    public $description;
    /**
     * @var array Владелец Данное поле не обязательно.
     * В этом случае будет назначен владельцем пользователь, от имени которого выполняется запрос (определяется по sid)
     **/
    public $owner;
    /**
     * @var array контрагент допускается значение 0 (ноль).
     **/
    public $client;
    /**
     * @var integer статус создаваемого проекта перечень допустимых значений для данного поля смотри в разделе статусы проектов
     **/
    public $status;
    /**
     * @var bool скрытый
     **/
    public $hidden;
    /**
     * @var bool имеет ли дату окончания
     **/
    public $hasEndDate;
    /**
     * @var string учитывается только в том случае, если параметр hasEndDate установлен в true
     **/
    public $endDate;
    /**
     * @var array группа проектов, необязательный параметр
     **/
    public $group;
    /**
     * @var array Надпроект
     */
    public $parent;
    /**
     * @var array проекта необязательный параметр
     **/
    public $auditors;
    /**
     * @var array менеджеры проекта необязательный параметр
     **/
    public $managers;
    /**
     * @var array исполнители по умолчанию проекта необязательный параметр
     **/
    public $workers;
    /**
     * @var array значения пользовательских полей проекта
     **/
    public $customData;

    /**
     * @var array
     */
    public $fields = [
        'id',
        'title',
        'description',
        'owner',
        'client',
        'status',
        'hidden',
        'hasEndDate',
        'endDate',
        'group',
        'parent',
        'auditors',
        'managers',
        'workers',
        'customData',
    ];

    /**
     * @param $object CabinetBase
     * @param $params
     * @return array
     */
    public function getProjectGroup($object, $params)
    {
        return [
            'id' => $this->projectGroups[$params][$object->base] ?? null
        ];
    }

    /**
     * @param $object
     * @param $params
     * @return string
     */
    public function getParent($object, $params)
    {
        return [
            'id' => $this->projectsOffers[$object->base] ?? null
        ];
    }
}
