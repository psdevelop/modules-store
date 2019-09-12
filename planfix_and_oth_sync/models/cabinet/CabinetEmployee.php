<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 01.04.2017
 */

namespace app\models\cabinet;

/**
 * Class CabinetEmployee
 * @property integer $id
 * @property string $synchronized
 * @property string $icq
 * @property string $skype
 * @property string $join_date
 * @property string $last_login
 * @property string $status
 * @property string $photo
 * @property integer $wants_email
 * @property integer $wants_sms
 * @property string $modified
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string $cell_phone
 * @property string $title
 * @property string $password
 * @property string $access
 * @property string $api_key
 * @property integer $timezone_id
 * @property string $employee_type
 * @property string $secret_key
 * @property bool $site_show
 * @package app\models\cabinet
 */
class CabinetEmployee extends CabinetBase
{
    public static $createdField = 'join_date';
    public static $table = 'employees';

    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullPlanfixName()
    {
        return sprintf('%s%d, %s', $this->getBasePrefix(), $this->id, $this->getFullName());
    }
}
