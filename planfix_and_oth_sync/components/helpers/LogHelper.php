<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 03.05.17
 * Time: 16:50
 */

namespace app\components\helpers;

use yii\log\FileTarget;
use yii\log\Logger;

class LogHelper
{
    const COLOR_BLACK = 'black';
    const COLOR_DARK_GRAY = 'dark_gray';
    const COLOR_BLUE = 'blue';
    const COLOR_LIGHT_BLUE = 'light_blue';
    const COLOR_GREEN = 'green';
    const COLOR_LIGHT_GREEN = 'light_green';
    const COLOR_CYAN = 'cyan';
    const COLOR_LIGHT_CYAN = 'light_cyan';
    const COLOR_RED = 'red';
    const COLOR_LIGHT_RED = 'light_red';
    const COLOR_PURPLE = 'purple';
    const COLOR_LIGHT_PURPLE = 'light_purple';

    const COLOR_BROWN = 'brown';
    const COLOR_YELLOW = 'yellow';
    const COLOR_LIGHT_GRAY = 'light_gray';
    const COLOR_WHITE = 'white';
    const COLOR_MAGENTA = 'magenta';

    protected static $foreground_colors =
        [
            self::COLOR_BLACK => '0;30',
            self::COLOR_DARK_GRAY => '1;30',
            self::COLOR_BLUE => '0;34',
            self::COLOR_LIGHT_BLUE => '1;34',
            self::COLOR_GREEN => '0;32',
            self::COLOR_LIGHT_GREEN => '1;32',
            self::COLOR_CYAN => '0;36',
            self::COLOR_LIGHT_CYAN => '1;36',
            self::COLOR_RED => '0;31',
            self::COLOR_LIGHT_RED => '1;31',
            self::COLOR_PURPLE => '0;35',
            self::COLOR_LIGHT_PURPLE => '1;35',
            self::COLOR_BROWN => '0;33',
            self::COLOR_YELLOW => '1;33',
            self::COLOR_LIGHT_GRAY => '0;37',
            self::COLOR_WHITE => '1;37',
        ];
    protected static $background_colors = [
        self::COLOR_BLACK => '40',
        self::COLOR_RED => '41',
        self::COLOR_GREEN => '42',
        self::COLOR_YELLOW => '43',
        self::COLOR_BLUE => '44',
        self::COLOR_MAGENTA => '45',
        self::COLOR_CYAN => '46',
        self::COLOR_LIGHT_GRAY => '47',
    ];

    protected static $spaceChar = "  ";

    /**
     * @param $logName
     */
    public static function initLog($logName)
    {
        /**
         * @var $target FileTarget
         */
        $target = \Yii::$app->log->getLogger()->dispatcher->targets['app'];
        $target->logFile = '@runtime/logs/' . $logName . '.log';
        $target->init();
    }

    public static function space($level)
    {
        $level = abs((int)$level);
        for ($i = 0; $i < $level; $i++) {
            print self::$spaceChar;
        }
    }

    // Returns colored string
    public static function getColoredString($string, $foreground_color = null, $background_color = null)
    {
        $colored_string = "";

        // Check if given foreground color found
        if (isset(static::$foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset(static::$background_colors[$background_color])) {
            $colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }

    public static function critical($string, $level = 0)
    {
        self::space($level);
        print self::getColoredString($string, self::COLOR_WHITE, self::COLOR_RED) . "\n";
        \Yii::getLogger()->log($string, Logger::LEVEL_ERROR);
    }

    public static function error($string, $level = 0)
    {
        self::space($level);
        print self::getColoredString($string, self::COLOR_RED) . "\n";
        \Yii::getLogger()->log($string, Logger::LEVEL_ERROR);
    }

    public static function info($string, $level = 0)
    {
        self::space($level);
        print self::getColoredString($string, self::COLOR_LIGHT_GRAY) . "\n";
        \Yii::getLogger()->log($string, Logger::LEVEL_WARNING);
    }

    public static function success($string, $level = 0)
    {
        self::space($level);
        print self::getColoredString($string, self::COLOR_GREEN) . "\n";
        \Yii::getLogger()->log($string, Logger::LEVEL_WARNING);
    }

    public static function warning($string, $level = 0)
    {
        self::space($level);
        print self::getColoredString($string, self::COLOR_YELLOW) . "\n";
        \Yii::getLogger()->log($string, Logger::LEVEL_WARNING);
    }

    public static function action($string, $level = 0)
    {
        self::space($level);
        print self::getColoredString($string, self::COLOR_BLACK, self::COLOR_LIGHT_GRAY) . "\n";
        \Yii::getLogger()->log($string, Logger::LEVEL_WARNING);
    }

    // Returns all foreground color names
    public static function getForegroundColors()
    {
        return array_keys(self::$foreground_colors);
    }

    // Returns all background color names
    public static function getBackgroundColors()
    {
        return array_keys(self::$background_colors);
    }
}
