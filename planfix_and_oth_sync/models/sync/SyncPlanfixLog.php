<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 24.04.17
 * Time: 9:57
 */

namespace app\models\sync;

/**
 * Class SyncPlanfixLog
 * @property string $created
 * @property string $date_from
 * @property string $date_to
 * @property integer $elapsed_time
 * @property boolean $is_success
 * @property string $message
 * @package app\models\sync
 */
class SyncPlanfixLog extends SyncBase
{
    public static $table = 'planfix_sync_log';

    /**
     * @param $type
     * @return array|null|self
     */
    public static function getLastSuccessSync($type = null)
    {
        return self::find()
            ->where(['=', 'is_success', 1])
            ->andWhere(['=', 'type', $type])
            ->orderBy(['date_to' => SORT_DESC])
            ->one();
    }
}
