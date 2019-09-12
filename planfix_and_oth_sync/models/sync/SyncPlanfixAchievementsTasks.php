<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 06.09.17
 * Time: 11:13
 */

namespace app\models\sync;


use app\models\cabinet\CabinetAchievements;

/**
 * Class SyncPlanfixAchievementsTasks
 * @package app\models\sync
 * @property string $status_task Статус задачи
 *
 */
class SyncPlanfixAchievementsTasks extends SyncBase
{
    /**
     * @var string
     */
    public static $table = 'planfix_achievements_tasks_sync';

    public function getCabinetObject($base)
    {
        parent::getCabinetObject($base);
        return $this->hasOne(CabinetAchievements::class, ['id' => $base . '_id']);
    }
}