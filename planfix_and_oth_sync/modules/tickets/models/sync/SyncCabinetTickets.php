<?php

namespace app\modules\tickets\models\sync;

use app\modules\tickets\services\EnvironmentService;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * @property integer $id
 * @property integer $ticket_id
 * @property integer $ticket_created
 * @property integer $ticket_modified
 * @property integer $planfix_id
 * @property integer $planfix_task_hash
 * @property integer $created_at
 * @property integer $updated_at
 */
class SyncCabinetTickets extends ActiveRecord
{
    public static function getDb() {
        return Yii::$app->dbPlanfixSync;
    }

    public static function tableName(): string
    {
        /** @var EnvironmentService $environmentService */
        $environmentService = Yii::$container->get(EnvironmentService::class);
        
        return $environmentService->getProjectEnvironment() === EnvironmentService::PROJECT_BLACK ?
            'sync_black_cabinet_tickets' : 'sync_cabinet_tickets';
    }

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [['ticket_id', 'ticket_created', 'ticket_modified', 'planfix_id', 'planfix_task_hash'], 'required'],
            [['id', 'ticket_id', 'planfix_id'], 'integer'],
            ['planfix_task_hash', 'string']
        ];
    }

    /**
     * @return array[][]
     */
    public function behaviors(): array
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'timestamp' => [
                    'class' => TimestampBehavior::class,
                    'value' => new Expression('NOW()'),
                ],
            ]
        );
    }
}
