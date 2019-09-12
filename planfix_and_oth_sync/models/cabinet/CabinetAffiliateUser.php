<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 29.03.2017
 */

namespace app\models\cabinet;

/**
 * Class CabinetAffiliateUser
 * @property integer $id
 * @property string $synchronized
 * @property string $join_date
 * @property string $last_login
 * @property integer $affiliate_id
 * @property string $status
 * @property integer $wants_email
 * @property integer $wants_sms
 * @property string $modified
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string $cell_phone
 * @property string $icq
 * @property string $skype
 * @property string $preferable_contact
 * @property string $title
 * @property string $password
 * @property integer $is_creator
 * @property integer $confirm_cell_phone
 * @property string $access
 * @property string $api_key
 * @property integer $timezone_id
 * @property integer $photo
 * @property integer $birthday
 * @property string $secret_key
 * @package app\models
 */
class CabinetAffiliateUser extends CabinetCompanyUser
{
    public static $table = 'affiliate_users';
    public static $createdField = 'join_date';
}
