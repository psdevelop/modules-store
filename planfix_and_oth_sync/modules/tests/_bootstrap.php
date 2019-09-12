<?php

use Dotenv\Dotenv;

defined('YII_APP_BASE_PATH') || define('YII_APP_BASE_PATH', __DIR__ . '/../../');
defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'test');

require_once YII_APP_BASE_PATH . '/vendor/autoload.php';
require_once YII_APP_BASE_PATH . '/components/helpers/env-helpers.php';
require_once YII_APP_BASE_PATH . '/vendor/yiisoft/yii2/Yii.php';

(Dotenv::create(YII_APP_BASE_PATH))->load();

$config = require(YII_APP_BASE_PATH .'/config/development/console.php');
