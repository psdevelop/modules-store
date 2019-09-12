<?php
return [
    'class' => 'app\components\PlanfixAPI',
    'apiKey' => getenv('planfix_api_key'),
    'apiSecret' => getenv('planfix_api_secret'),
    'account' => getenv('planfix_api_account'),
    'login' => getenv('planfix_login'),
    'password' => getenv('planfix_password'),
];