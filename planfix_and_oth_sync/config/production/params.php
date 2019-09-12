<?php
$commonConfig = require(__DIR__ . '/../common/planfix_config.php');

$environmentConfig = [
    'groups' => [
        'WEBMASTER' => 7630,
        'ADVERTISER' => 7628,
    ],
    'cabinets' => [
        'leads' => [
            'manager' => 'manager.leads.su'
        ],
        'trade' => [
            'manager' => 'manager.leads.black'
        ]
    ],
    'customStatuses' => [
        'testing' => [
            'id' => 109,
            'ruValue' => 'В тестировании'
        ],
        'feedback' => [
            'id' => 127,
            'ruValue' => 'В обратной связи'
        ],
    ],
    'phantoms' => [
        'leads' => 0,
        'trade' => 1
    ],
    'filters' => [
        'finalizedPayoutRequests' => 5333892,
        'scrumTasks' => 5167978
    ],
    'handbooks' => [
        'sprints' => 22790,
    ],
    'templates' => [
        'taskPayout' => 12127158,
        'user' => 98438,
        'company' => 98439,
        'offerTask' => 13103836,
        'bonusTask' => 13133628,
    ],
    'projects' => [
        'fastPaymentsLeads' => 491280,
        'fastPaymentsTrade' => 491282,
        'jivoChatProjects' => [
            'leads' => 488810,
            'trade' => 488812
        ]
    ],
    'projectGroups' => [
        'offers' => [
            'leads' => 2,
            'trade' => 4
        ]
    ],
    'projectsOffers' => [
        'leads' => 494528,
        'trade' => 494530
    ],
    'customFields' => [
        'date_create' => 55624,
        'status' => 55622,
        'network' => 55620,
        'projectNetwork' => 72468,
        'modified' => 55626,
        'note' => 52904,
        'scrumPoints' => 62552,
        'sprint' => 62472,
        'scrumType' => 62610,
        'cabinetOwner' => 71422,
        'cabinetUrl' => 72470,
        'offerStatus' => 72476,
        'totalSum' => 72538,
        'achieveType' => 72536,
        'cabinetId' => 72540,
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
