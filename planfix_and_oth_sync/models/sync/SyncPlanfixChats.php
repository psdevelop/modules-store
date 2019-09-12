<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 20.04.17
 * Time: 14:14
 */

namespace app\models\sync;

use app\components\helpers\LogHelper;
use app\models\cabinet\CabinetChat;

/**
 * Class SyncPlanfixNotes
 * @property $status_sync
 *
 * @package app\models\sync
 */
class SyncPlanfixChats extends SyncBase
{

    public static $table = 'planfix_chats_sync';

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
            $sync->save();
        }
    }

    public function getCabinetObject($base)
    {
        parent::getCabinetObject($base);
        return $this->hasOne(CabinetChat::class, ['id' => $base . '_id']);
    }
}
