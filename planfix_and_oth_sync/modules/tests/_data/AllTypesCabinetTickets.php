<?php

use app\modules\tickets\enum\TicketAccountTypeEnum;
use app\modules\tickets\enum\TicketCategoryEnum;
use app\modules\tickets\enum\TicketCategorySubEnum;
use app\modules\tickets\models\cabinet\ExternalCabinetInfo;
use app\modules\tickets\models\cabinet\ExternalTicketInfo;

return [
    [
        'ticket' => [
            'id' => 1,
            'project' => 'leads',
            'title' => 'Тестовое название',
            'description' => 'Тестовое описание',
            'accountId' => 321,
            'accountType' => TicketAccountTypeEnum::TYPE_AFFILIATE,
            'accountCompany' => 'Тестовое CompanyName',
            'category' => TicketCategoryEnum::CATEGORY_AK,
            'subcategory' => TicketCategorySubEnum::SUB_REPLENISHMENT,
            'additionalInformation' => new ExternalTicketInfo([
                'externalAmount' => 777,
                'sourceWallet' => 'TestWallet',
                'protectionCode' => 'TestCode',
                'cabinetInfo' => new ExternalCabinetInfo([
                    'networkTitle' => 'Яндекс.Директ',
                    'login' => 'test_login',
                ])
            ]),
        ],

        'expectedTitle' => '#1 - Агентский кабинет - пополнение',
        'expectedDescription' => require __DIR__ . '/descriptions/ak_replenishment.php',
    ],
    [
        'ticket' => [
            'id' => 2,
            'project' => 'leads',
            'title' => 'Тестовое название',
            'description' => 'Тестовое описание',
            'accountId' => 321,
            'accountType' => TicketAccountTypeEnum::TYPE_AFFILIATE,
            'accountCompany' => 'Тестовое CompanyName',
            'category' => TicketCategoryEnum::CATEGORY_AK,
            'subcategory' => TicketCategorySubEnum::SUB_CREATE,
            'additionalInformation' => new ExternalTicketInfo([
                'cabinetInfo' => new ExternalCabinetInfo([
                    'networkTitle' => 'Яндекс.Директ',
                    'login' => 'test_login',
                    'name' => 'Test1',
                    'lastName' => 'Test2',
                    'middleName' => 'Test3',
                    'email' => 'test@example.com',
                    'phone' => '1234567890',
                ])
            ]),
        ],

        'expectedTitle' => '#2 - Агентский кабинет - создание',
        'expectedDescription' => require __DIR__ . '/descriptions/ak_creature.php',
    ],
    [
        'ticket' => [
            'id' => 3,
            'project' => 'black',
            'title' => 'Тестовое название',
            'description' => 'Тестовое описание',
            'accountId' => 321,
            'accountType' => TicketAccountTypeEnum::TYPE_AFFILIATE,
            'accountCompany' => 'Тестовое CompanyName',
            'category' => TicketCategoryEnum::CATEGORY_AK,
            'subcategory' => TicketCategorySubEnum::SUB_TRANSFER,
            'additionalInformation' => new ExternalTicketInfo([
                'externalAmount' => 1200,
                'cabinetInfoSource' => new ExternalCabinetInfo([
                    'networkTitle' => 'Яндекс.Директ',
                    'login' => 'test_login',
                    'name' => 'Test1',
                    'lastName' => 'Test2',
                    'middleName' => 'Test3',
                    'email' => 'test@example.com',
                    'phone' => '1234567890',
                ]),
                'cabinetInfoDestination' => new ExternalCabinetInfo([
                    'networkTitle' => 'Google.Adwords',
                    'login' => 'test_login',
                    'name' => 'Test1',
                    'lastName' => 'Test2',
                    'middleName' => 'Test3',
                    'email' => 'test@example.com',
                    'phone' => '1234567890',
                ])
            ]),
        ],

        'expectedTitle' => '#3 - Агентский кабинет - перевод',
        'expectedDescription' => require __DIR__ . '/descriptions/ak_transfer.php',
    ],
    [
        'ticket' => [
            'id' => 4,
            'project' => 'black',
            'title' => 'Тестовое название',
            'description' => 'Тестовое описание',
            'accountId' => 321,
            'accountType' => TicketAccountTypeEnum::TYPE_AFFILIATE,
            'accountCompany' => 'Тестовое CompanyName',
            'category' => TicketCategoryEnum::CATEGORY_AK,
            'subcategory' => TicketCategorySubEnum::SUB_OTHER,
        ],

        'expectedTitle' => '#4 - Агентский кабинет - другое',
        'expectedDescription' => require __DIR__ . '/descriptions/ak_other.php',
    ],
    [
        'ticket' => [
            'id' => 5,
            'project' => 'leads',
            'title' => 'Тестовое название',
            'description' => 'Тестовое описание',
            'accountId' => 321,
            'accountType' => TicketAccountTypeEnum::TYPE_AFFILIATE,
            'accountCompany' => 'Тестовое CompanyName',
            'category' => TicketCategoryEnum::CATEGORY_AK,
            'subcategory' => TicketCategorySubEnum::SUB_MODERATION,
            'additionalInformation' => new ExternalTicketInfo([
                'countryTitle' => 'Россия',
                'platformTitle' => 'Оффер',
                'advertisingCompanyId' => 'company id',
                'cabinetInfo' => new ExternalCabinetInfo([
                    'networkTitle' => 'Яндекс.Директ',
                    'login' => 'test_login',
                    'name' => 'Test1',
                    'lastName' => 'Test2',
                    'middleName' => 'Test3',
                    'email' => 'test@example.com',
                    'phone' => '1234567890',
                ]),
            ]),
        ],

        'expectedTitle' => '#5 - Агентский кабинет - модерация',
        'expectedDescription' => require __DIR__ . '/descriptions/ak_moderation.php',
    ],
];