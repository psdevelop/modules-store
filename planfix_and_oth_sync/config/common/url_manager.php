<?php
/**
 * Url Manager
 */
return [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        '<modules:\w+>/<controller:\w+>/<action>/<handler:\w+>/<target:\w+>' => '<modules>/<controller>/<action>',
    ],
];