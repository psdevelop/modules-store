<?php
return [
    'class' => 'app\components\PlanfixWebService',
    'account' => getenv('planfix_api_account'),
    'login' => getenv('planfix_login'),
    'password' => getenv('planfix_password'),
];