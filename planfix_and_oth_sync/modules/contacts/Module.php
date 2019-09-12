<?php

namespace app\modules\contacts;

use Yii;
use yii\base\Module as BaseModule;
use yii\console\Application as ConsoleApplication;

/**
 * Class Module
 * Класс модуля синхронизации контактов с PF
 * @package app\modules\contacts
 */
class Module extends BaseModule
{
    /** @const string */
    const COMMANDS_NAMESPACE = 'app\modules\contacts\commands';

    /** @var string */
    public $controllerNamespace = 'app\modules\contacts\controllers';

    public function init()
    {
        parent::init();

        if (Yii::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = self::COMMANDS_NAMESPACE;
        }
    }
}
