<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 26.07.17
 * Time: 12:00
 */

namespace app\components\helpers;


class TimerHelper
{
    protected static $timeTicksQueue = [];
    protected static $timeTicks = [];
    protected static $sign = "        ";
    protected static $printLineLength = 40;
    protected static $timerWarning = 100;
    public static $verbose = true;


    /**
     * Установка таймера
     * @param null $id
     */
    public static function timerRun($id = null)
    {
        if(!self::$verbose){
            return;
        }

        if (php_sapi_name() !== 'cli') {
            return;
        }
        if ($id) {
            self::$timeTicks[(string)$id] = microtime(true);
            return;
        }
        self::$timeTicksQueue[] = microtime(true);
    }

    /**
     * Закрыть таймер, принт результата
     * @param null $id
     * @param string $comment
     * @param null $pin
     * @param bool $line
     */
    public static function timerStop($id = null, $comment = '', $pin = null, $line = false)
    {
        if(!self::$verbose){
            return;
        }
        if (php_sapi_name() !== 'cli') {
            return;
        }
        if ($id && isset(self::$timeTicks[(string)$id])) {
            $levelTree = 0;
            $prevTick = self::$timeTicks[(string)$id];
        } else {
            $levelTree = count(self::$timeTicksQueue);
            $prevTick = array_pop(self::$timeTicksQueue);
        }
        $elapsed = round((microtime(true) - $prevTick) * 1000, 3);

        $comment = str_repeat("  ", $levelTree <= 1 ? 0 : ($levelTree - 1)) . ($levelTree <= 1 ? "" : "↓ ") . $comment;

        $commentPrint = mb_substr($comment, 0, self::$printLineLength);
        do {
            $commentPrint .= ".";
        } while (mb_strlen($commentPrint) < (self::$printLineLength + 1));

        print "\t";
        print (!$id ? LogHelper::getColoredString($commentPrint, $levelTree <= 1 ? LogHelper::COLOR_WHITE : LogHelper::COLOR_DARK_GRAY) : LogHelper::getColoredString($commentPrint, LogHelper::COLOR_LIGHT_BLUE)) . ": ";
        print LogHelper::getColoredString(self::humanized($elapsed), $levelTree <= 1 ? LogHelper::COLOR_YELLOW : LogHelper::COLOR_LIGHT_GRAY);
        self::printElapsed($elapsed, $pin);
        print ($line ? "" : "\n");

    }

    /**
     * Печать исполнения
     * @param $elapsed
     * @param $pin
     */
    public static function printElapsed($elapsed, $pin)
    {
        if(!self::$verbose){
            return;
        }
        print " \t";
        if ($elapsed <= self::$timerWarning) {
            print LogHelper::getColoredString("+", LogHelper::COLOR_GREEN);
            self::printPin($pin, LogHelper::COLOR_GREEN);
        } else {
            $rate = round($elapsed / self::$timerWarning);
            print LogHelper::getColoredString("x$rate", LogHelper::COLOR_WHITE, LogHelper::COLOR_RED);
            self::printPin($pin, LogHelper::COLOR_RED);
        }
    }

    /**
     * Тег таймера
     * @param $pin
     * @param string $color
     */
    public static function printPin($pin, $color = LogHelper::COLOR_LIGHT_GRAY)
    {
        if(!self::$verbose){
            return;
        }
        print " \t";
        print LogHelper::getColoredString("$pin", LogHelper::COLOR_WHITE, $color);
    }

    /**
     * Ms - в человеческий вид :)
     * @param $ms
     * @return string
     */
    public static function humanized($ms)
    {
        if ($ms < 1) {
            return $ms . "ms";
        }
        $sec = floor($ms / 1000);
        $ms = $ms % 1000;
        $min = floor($sec / 60);
        $sec = $sec % 60;
        $hr = floor($min / 60);
        $min = $min % 60;
        $day = floor($hr / 60);
        $hr = $hr % 60;

        $timeMap = [
            'd' => $day,
            'h' => $hr,
            'm' => $min,
            's' => $sec,
            'ms' => $ms,
        ];
        $ret = '';
        foreach ($timeMap as $mark => $value) {
            $ret .= $value ? "$value$mark " : "";
        }

        return $ret;
    }
}