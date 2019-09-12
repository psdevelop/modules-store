<?php
return [
    'targets' => [
        'app' => [
            'class' => 'yii\log\FileTarget',
            'levels' => ['error', 'warning'],
            'logVars' => []
        ],
        'application' => [
            'class' => 'yii\log\FileTarget',
            'levels' => ['error', 'warning'],
            'logVars' => []
        ],
    ],
];
