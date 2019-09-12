<?php

namespace app\exceptions;

use app\components\helpers\LogHelper;
use yii\base\UserException;

/**
 * User: bengraf
 * Date: 17.07.17
 * Time: 11:23
 */
class SyncException extends UserException
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        LogHelper::action($message . ', ' . $code);
    }
}
