<?php
/**
 * Yii bootstrap file.
 */
class Yii extends \yii\BaseYii
{
    /**
     * @var BaseApplication|WebApplication|ConsoleApplication the application instance
     */
    public static $app;
}

/**
 * Class BaseApplication
 *
 * @property \app\components\CabinetAPI $cabinetApi
 * @property \app\components\PlanfixAPI $planfixApi
 * @property \app\components\PlanfixWebService $planfixWs
 *
 * @property \yii\db\Connection $dbPlanfixSync
 * @property \yii\db\Connection $dbLeads
 * @property \yii\db\Connection $dbTradeLeads
 * @property
 */
abstract class BaseApplication extends yii\base\Application
{
}

/**
 * Class WebApplication
 *
 */
class WebApplication extends yii\web\Application
{
}

/**
 * Class ConsoleApplication
 *
 */
class ConsoleApplication extends yii\console\Application
{
}