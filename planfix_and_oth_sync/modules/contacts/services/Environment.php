<?php

namespace app\modules\contacts\services;

use app\modules\contacts\contracts\repositories\IContactsSync;
use app\modules\contacts\repositories\LeadsContactsSync;
use app\modules\contacts\repositories\BlackContactsSync;
use Yii;
use app\services\Environment as AbstractEnvironment;

/**
 * Class Environment
 * @package app\modules\contacts\services
 */
class Environment extends AbstractEnvironment
{
    /** @inheritDoc */
    public function init()
    {
        $contactsSyncClass = ($this->project === AbstractEnvironment::PROJECT_LEADS)
            ? LeadsContactsSync::class
            : BlackContactsSync::class;

        Yii::$container->setSingletons([
            IContactsSync::class => $contactsSyncClass,
        ]);
    }
}