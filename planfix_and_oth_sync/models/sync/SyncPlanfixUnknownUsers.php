<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 21.04.17
 * Time: 10:20
 */

namespace app\models\sync;

/**
 * Class SyncPlanfixUnknownUsers
 * @package app\models\sync
 * @property integer $planfix_id
 * @property integer $planfix_userid
 * @property string $email
 */
class SyncPlanfixUnknownUsers extends SyncBase
{

    public static $table = 'planfix_unknown_users_sync';
}
