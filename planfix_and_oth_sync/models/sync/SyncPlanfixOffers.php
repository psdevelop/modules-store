<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 05.09.17
 * Time: 13:23
 */

namespace app\models\sync;


use app\components\helpers\LogHelper;
use app\models\cabinet\CabinetOffer;

class SyncPlanfixOffers extends SyncBase
{
    /**
     * @var string
     */
    public static $table = 'planfix_offers_sync';

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
            $sync->save();
        }
    }

    public function getCabinetObject($base)
    {
        parent::getCabinetObject($base);
        return $this->hasOne(CabinetOffer::class, ['id' => $base . '_id']);
    }

}
