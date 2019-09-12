<?php

namespace app\modules\tickets\enum;

class AgencyAdNetworkTypeEnum
{
    /** @var string */
    const TYPE_YANDEX_DIRECT = 'yandex_direct';

    /** @var string */
    const TYPE_GOOGLE = 'google';

    /** @var string */
    const TYPE_VK = 'vk';

    /** @var string */
    const TYPE_MY_TARGET = 'my_target';

    public static function getAll(): array
    {
        return [
            self::TYPE_YANDEX_DIRECT => 'Яндекс.Директ',
            self::TYPE_GOOGLE => 'Google.Adwords',
            self::TYPE_VK => 'Вконткте',
            self::TYPE_MY_TARGET => 'MyTarget',
        ];
    }
}
