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

/**
 * Class SyncPlanfixCompanies
 * @package app\models\sync
 */
class SyncPlanfixCompanies extends SyncBase
{

    public static $table = 'planfix_company_sync';

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

            $sync->type = $object['type'];
            $sync->save();
        }
    }

    /**
     * string leads / trade
     * @param $base
     * @return \yii\db\ActiveQuery
     */
    public function getCabinetObject($base)
    {
        parent::getCabinetObject($base);
        $class = str_replace('Base', ucfirst($this->type), CabinetBase::class);
        return $this->hasOne($class, ['id' => $base . '_id']);
    }

}
