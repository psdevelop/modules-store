<?php
namespace app\components\enums;
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 05.02.18
 * Time: 12:34
 */
abstract class AbstractEnum
{
    abstract public function getValues();
    abstract public function getClientValues();

    protected static $instances = [];

    public static function instance()
    {
        return static::$instances[static::class] ?? (static::$instances[static::class] = new static());
    }

    /**
     * @param $key
     * @return null
     */
    public static function getClientValue($key)
    {
        $values = static::instance()->getClientValues();
        return $values[$key] ?? null;
    }
}
