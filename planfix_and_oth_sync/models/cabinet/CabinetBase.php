<?php
/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 01.04.2017
 */

namespace app\models\cabinet;

use yii\db\ActiveRecord;
use yii\db\Connection;

class CabinetBase extends ActiveRecord
{
    public static $db = 'dbLeads';
    public static $table;

    public $base;
    public $leads_id;
    public $trade_id;

    public static $modifiedField = 'modified';

    public $post;

    /**
     * tableName
     * @return string
     */
    public static function tableName()
    {
        return static::$table;
    }

    /**
     * @return Connection
     */
    public static function getDb()
    {
        return \Yii::$app->{self::$db};
    }

    public function __get($name)
    {
        $data = parent::__get($name);
        if ($data instanceof self) {
            return $data->setBaseIds($this);
        }

        return $data;
    }

    /**
     * Установка базы
     * @param $componentName
     */
    public static function setDb($componentName)
    {
        self::$db = $componentName;
    }

    /**
     * @param $object1
     * @param null $object2
     * @return array
     */
    public static function merge($object1, $object2 = null)
    {
        $object1 = (array)$object1;
        $object2 = (array)$object2;

        $result = [];
        foreach ($object1 as $key => $value) {
            if (!isset($object2[$key]) || empty($object2[$key])) {
                $result[$key] = $value;
                continue;
            }

            if (!empty($object2[$key]) && empty($object1[$key])) {
                $result[$key] = $value;
                continue;
            }

            if (!empty($object2[$key]) && empty($object1[$key])) {
                $result[$key] = $value;
                continue;
            }
        }
        return $result;
    }

    /**
     * @return null
     */
    public function setDbById()
    {
        $base = null;
        if ($this->leads_id) {
            $base = 'leads';
        }

        if ($this->trade_id) {
            $base = 'trade';
        }

        if (!$base) {
            return null;
        }
        $this->base = $base;
        return self::setDbByBase($base);
    }

    /**
     * Установка коннекта Leads / Trade
     * @param $base
     */
    public static function setDbByBase($base)
    {
        return self::setDb('db' . ($base == 'trade' ? 'Trade' : '') . 'Leads');
    }

    /**
     * @param $base self|string
     * @return $this
     */
    public function setBaseIds($base)
    {
        if(!$this){
            return null;
        }
        if (is_string($base)) {
            $this->{$base . "_id"} = $this->id;
            $this->base = $base;
        } else {
            $this->leads_id = $base->leads_id ? $this->id : null;
            $this->trade_id = $base->trade_id ? $this->id : null;
            $this->base = $base->base;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @return string
     */
    public function getBasePrefix()
    {
        $ids = [];
        if ($_id = $this->leads_id) {
            $ids[] = 'Л' . $_id;
        }

        if ($_id = $this->trade_id) {
            $ids[] = 'Т' . $_id;
        }

        return implode(' / ', $ids);
    }
}
