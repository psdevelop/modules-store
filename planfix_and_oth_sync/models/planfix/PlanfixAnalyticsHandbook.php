<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 29.06.17
 * Time: 11:42
 */

namespace app\models\planfix;


class PlanfixAnalyticsHandbook extends PlanfixAnalytics
{
    public static $handBook;
    public static function getHandbook($id, $by = null, $find = null)
    {
        $response = static::instance()->planfixApi->api('analitic.getHandbook', [
            'handbook' => [
                'id' => $id
            ]
        ], true);

        if (!isset($response['data']['records']['record'])) {
            return [];
        }

        $records = $response['data']['records']['record'];

        foreach ($records as $index => &$record) {

            if($record['isGroup'] != false){
                unset($records[$index]);
                continue;
            }

            foreach ($record['value'] as $attribute) {
                $attribute = $attribute['@attributes'];
                $attrValue = isset($attribute['value']) ? $attribute['value'] : null;
                $attrName = isset($attribute['name']) ? $attribute['name'] : null;
                $record[$attrName]=$attrValue;
            }
            unset($record['value']);
        }
        if(!$by) {
            return self::$handBook = $records;
        }

        $stack = [];
        foreach ($records as $index => &$record) {
            if(!isset($record[$by])) {
                continue;
            }
            $stack[$record[$by]]=$record;
        }

        if ($find) {
            if (isset($stack[$find])) {
                return $stack[$find];
            }
            return array_shift($stack);
        }

        return self::$handBook = $stack;
    }

    /**
     * @param $string
     * @return array
     */
    public static function getFromHandBook($string, $property= null)
    {
        if($property) {
            return isset(self::$handBook[$string][$property]) ? self::$handBook[$string][$property] : null;
        }
        return isset(self::$handBook[$string]) ? self::$handBook[$string] : [];
    }
}