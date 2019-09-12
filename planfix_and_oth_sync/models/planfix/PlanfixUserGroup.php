<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 01.04.2017
 */

namespace app\models\planfix;


class PlanfixUserGroup extends PlanfixBase
{
    public $objectName = 'userGroup';
    public $objectKey = 'id';

    public $fields = [
        'id',
        'name',
        'userCount'
    ];
}
