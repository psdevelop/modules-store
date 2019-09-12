<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 06.09.17
 * Time: 11:13
 */

namespace app\models\sync;


use app\models\cabinet\CabinetBase;
use app\models\cabinet\CabinetOfferToDomains;

class SyncPlanfixOffersTasks extends SyncBase
{
    /**
     * @var string
     */
    public static $table = 'planfix_offers_tasks_sync';

    public function getCabinetObject($base)
    {
        parent::getCabinetObject($base);
        return $this->hasOne(CabinetOfferToDomains::class, ['id' => $base . '_id']);
    }
}