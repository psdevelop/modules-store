<?php

namespace app\bootstrap;

use app\modules\tickets\services\EnvironmentService as TicketsEnvironment;
use app\modules\contacts\services\Environment as ContactsEnvironment;
use Yii;
use yii\base\BootstrapInterface;

class DIContainer implements BootstrapInterface
{
    /** @inheritdoc */
    public function bootstrap($app)
    {
        Yii::$container->setSingletons([
            TicketsEnvironment::class => TicketsEnvironment::class,
            ContactsEnvironment::class => ContactsEnvironment::class
        ]);
    }
}
