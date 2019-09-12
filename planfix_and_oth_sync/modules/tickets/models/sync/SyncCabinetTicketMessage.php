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
 * @property integer $ticket_message_id
 * @property integer $ticket_message_created
 * @property integer $ticket_message_modified
 * @property integer $planfix_message_id
 * @property string $hash
 * @property integer $created_at
 * @property integer $updated_at
 */
class SyncCabinetTicketMessage extends ActiveRecord
{
    public static function getDb() {
        return Yii::$app->dbPlanfixSync;
    }

    public static function tableName(): string
    {
        /** @var EnvironmentService $environmentService */
        $environmentService = Yii::$container->get(EnvironmentService::class);

        return $environmentService->getProjectEnvironment() === EnvironmentService::PROJECT_BLACK ?
            'sync_black_cabinet_ticket_messages' : 'sync_cabinet_ticket_messages';
    }

    /**
     * @return array[][]
     */
    public function rules(): array
    {
        return [
            [
                [
                    'ticket_message_id',
                    'ticket_message_created',
                    'ticket_message_modified',
                    'planfix_message_id',
                    'hash'
                ],
                'required'
            ],
            [['id', 'ticket_message_id', 'planfix_message_id'], 'integer'],
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
