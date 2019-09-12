<?php

namespace app\models\links;

use app\components\enums\EntityNamesEnum;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class LinkOfferTask extends LinkModel
{
    public $id;
    public $type;
    public $platform;

    protected $availableModules = [
        self::MODULE_API,
    ];

    protected static $entityNamespace = EntityNamesEnum::OFFER_TASK;
}
