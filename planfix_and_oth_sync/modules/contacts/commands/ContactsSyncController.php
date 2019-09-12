<?php

namespace app\modules\contacts\commands;

use app\components\filters\UniqueAccess;
use Exception;
use Yii;
use yii\base\Module;
use yii\console\Controller;
use app\modules\contacts\services\ContactsSync as ContactsSyncService;
use app\modules\contacts\services\Environment;

/**
 * Class ContactsSyncController
 * Класс задания синхронизации новых контактов
 * @package app\modules\contacts\commands
 */
class ContactsSyncController extends Controller
{
    /** @var Environment */
    private $environment;

    /** @var string[] */
    private $allowTypeSync;

    public function __construct(
        string $id,
        Module $module,
        Environment $environment,
        array $config = []
    )
    {
        $this->environment = $environment;

        $this->allowTypeSync = [
            Environment::PROJECT_LEADS,
            Environment::PROJECT_BLACK,
        ];

        parent::__construct($id, $module, $config);
    }

    /** @inheritDoc */
    public function behaviors()
    {
        return [
            'UniqueAccess' => [
                'class' => UniqueAccess::class,
            ]
        ];
    }

    /**
     * Синхронизирует контакты из leads/black в planfix
     * @param string $type
     * @throws Exception
     */
    public function actionSyncToPlanfix(string $type)
    {
        if (!in_array($type, $this->allowTypeSync)) {
            throw new Exception("Invalid project type '{$type}'!");
        }

        $this->environment->setProject($type);
        Yii::$container->get(ContactsSyncService::class)->syncToPlanfix();
    }
}
