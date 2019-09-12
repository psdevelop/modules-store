<?php

namespace app\models\links;
use app\components\enums\EntityNamesEnum;

/**
 *┌───────────────────────────────────────────────────┐
 *│┏━┓╻  ┏━┓┏┓╻┏━╸╻╻ ╻   ╻  ╻┏┓╻╻┏ ┏━┓┏━╸┏━┓╻ ╻╻┏━╸┏━╸│
 *│┣━┛┃  ┣━┫┃┗┫┣╸ ┃┏╋┛   ┃  ┃┃┗┫┣┻┓┗━┓┣╸ ┣┳┛┃┏┛┃┃  ┣╸ │
 *│╹  ┗━╸╹ ╹╹ ╹╹  ╹╹ ╹   ┗━╸╹╹ ╹╹ ╹┗━┛┗━╸╹┗╸┗┛ ╹┗━╸┗━╸│
 *└───────────────────────────────────────────────────┘
 * Class LinkBonusTask
 * @package app\models\links
 */
class LinkAchieveTask extends LinkModel
{
    public $id;
    public $type;
    public $platform;

    protected $availableModules = [
        self::MODULE_API,
        self::MODULE_SERVICE
    ];

    protected static $entityNamespace = EntityNamesEnum::ACHIEVE_TASK;
}
