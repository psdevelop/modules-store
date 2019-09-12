<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 18.04.17
 * Time: 15:19
 */

namespace app\models\sync;

use app\components\helpers\LogHelper;
use app\models\cabinet\CabinetBase;

class SyncPlanfixUsers extends SyncBase
{
    public static $table = 'planfix_users_sync';

    /**
     * @param $objects SyncBase[]
     */
    public static function updateSync($objects)
    {
        LogHelper::action("Prepare " . static::$table . " table for sync...");
        foreach ($objects as $object) {
            if (!$object['sync_id']) {
                $sync = new static();
                $sync->status_sync = 'add';
            } else {
                if (!$sync = static::findOne(['=', 'id', $object['sync_id']])) {
                    continue;
                }
                if (!$sync->planfix_id) {
                    $sync->status_sync = 'add';
                } else {
                    $sync->status_sync = 'update';
                }
            }

            $sync->leads_id = $object['leads_id'];
            $sync->trade_id = $object['trade_id'];
            $sync->leads_cid = $object['leads_cid'];
            $sync->trade_cid = $object['trade_cid'];

            $sync->type = $object['type'];
            $sync->save();
        }
    }

    public function getCabinetObject($base)
    {
        if (!$this->type) {
            return null;
        }
        parent::getCabinetObject($base);
        $class = str_replace('Base', ucfirst($this->type) . 'User', CabinetBase::class);
        return $this->hasOne($class, ['id' => $base . '_id']);
    }
}
