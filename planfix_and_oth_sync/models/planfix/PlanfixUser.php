<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 01.04.2017
 */

namespace app\models\planfix;


class PlanfixUser extends PlanfixBase
{
    public $objectName = 'user';
    public $objectKey = 'id';

    public $fields = [
        'id',
        'name',
        'lastName',
        'email',
        'role',
        'status'
    ];

}
