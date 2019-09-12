<?php
$commonConfig = require(__DIR__ . '/../common/planfix_config.php');

$environmentConfig = [
    'groups' => [
        'WEBMASTER' => 7462,
        'ADVERTISER' => 7390,
    ],
    'cabinets' => [
        'leads' => [
            'manager' => 'manager.azatk.leads'
        ],
        'trade' => [
            'manager' => 'manager.azatk.leads'
        ]
    ],
    'customStatuses' => [
        'testing' => [
            'id' => 103,
            'ruValue' => 'В тестировании'
        ],
        'feedback' => [
            'id' => 104,
            'ruValue' => 'В обратной связи'
        ],
    ],
    'phantoms' => [
        'leads' => 3316616,
        'trade' => 1
    ],
    'filters' => [
        'finalizedPayoutRequests' => 5208108,
        'scrumTasks' => 5501216
    ],
    'handbooks' => [
        'sprints' => 22790
    ],
    'templates' => [
        'taskPayout' => 11122354,
        'user' => 391370,
        'company' => 391371,
        'offerTask' => 13478982,
        'bonusTask' => 16375720,
        'ticketTaskCreate' => 852710,
        'ticketTaskRefill' => 861714,
        'ticketTaskTransfer' => 861880,
        'ticketTaskModeration' => 861922,
        'ticketTaskOther' => 861956,
    ],
    'projects' => [
        'fastPaymentsLeads' => 429540,
        'fastPaymentsTrade' => 429540,
        'jivoChatProjects' => [
            'leads' => 489002,
            'trade' => 489000
        ],
    ],
    'projectGroups' => [
        'offers' => [
            'leads' => 42402,
            'trade' => 42850
        ]
    ],
    'projectsOffers' => [
        'leads' => 506658,
        'trade' => 506660
    ],
    'customFields' => [
        'date_create' => 50540,
        'status' => 52708,
        'network' => 51386,
        'projectNetwork' => 74698,
        'modified' => 52148,
        'note' => 50110,
        'scrumPoints' => 62552,
        'sprint' => 62472,
        'scrumType' => 62610,
        'cabinetOwner' => 71424,
        'cabinetUrl' => 75724,
        'offerStatus' => 76352,
        'totalSum' => 55062,
        'achieveType' => 87504,
        'cabinetId' => 87502,
        'ticketAccountType' => 11680,
        'ticketAccountId' => 11618,
        'ticketCategoryName' => 11682,
        'ticketSubCategoryName' => 11684,
    ],
];

return [
    'adminEmail' => 'rb@leads.su',
    'planfix' => [
        'config' => array_merge(
            $environmentConfig,
            $commonConfig
        )
    ]
];
