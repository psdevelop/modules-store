<?php

use Dotenv\Dotenv;

// comment out the following two lines when deployed to production


defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../vendor/autoload.php');
require_once __DIR__ . '/../components/helpers/env-helpers.php';
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

(Dotenv::create(__DIR__ . '/../'))->load();

$e = str_replace("###", "", getenv('planfix_password'));
putenv("planfix_password=$e");
$enviroment = getenv('app_config') ? getenv('app_config') : 'development';
$configPath = __DIR__ . '/../config/' . $enviroment . '/';
$config = require($configPath . 'web.php');

(new yii\web\Application($config))->run();
