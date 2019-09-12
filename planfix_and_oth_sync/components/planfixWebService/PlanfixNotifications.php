<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 10.05.17
 * Time: 14:14
 */

namespace app\components\planfixWebService;

use app\exceptions\SyncException;
use app\components\helpers\LogHelper;
use app\components\PlanfixWebService;
use app\models\planfix\PlanfixBase;

class PlanfixNotifications
{
    /**
     * @var PlanfixWebService
     */
    protected $planfixWs;
    /**
     * @var PlanfixNotifications
     */
    protected static $instance;
    public $currentNotificationParams = [];
    public $newNotificationParams = [];

    protected $dumpDirectory = __DIR__ . '/../../../dumps';
    protected $dumpFile = 'dump_notices.json';
    protected $fixFile = 'fixed_notices.json';

    public function __construct()
    {
        $this->planfixWs = \Yii::$app->planfixWs;
    }

    public static function init()
    {
        return self::$instance = new self();
    }

    public static function getInstance()
    {
        return self::$instance ?? null;
    }

    public static function fixNotifications($additionalFixFile = null)
    {
        try {
            $result = self::getInstance()->fixUsersCurrentNotifications(PlanfixBase::instance()->planfixUsers, $additionalFixFile);
            LogHelper::success("Оповещения успешно зафиксированы!");
            return $result;
        } catch (SyncException $exception) {
            LogHelper::critical("Не удалось зафиксировать оповощения");
            return $exception;
        }
    }

    public static function setReady($levels)
    {
        $instance = self::getInstance();
        $instance::fixNotifications();
        $instance->newNotificationParams = $instance->setAllUsersNotificationsLevel(
            $instance->currentNotificationParams,
            $instance->getNotificationLevel($levels)
        );
        return $instance;
    }

    public static function setNewNotifications()
    {
        LogHelper::info("Set silence...");
        if (!$instance = self::getInstance()) {
            LogHelper::critical("You can not switch off notifications...");
            return false;
        }

        if (!$instance->setUsersNotifications($instance->newNotificationParams)) {
            LogHelper::critical("Установка новых уведомлений не удалась...");
        }
        LogHelper::success("Новые оповещения включены!");
        return true;
    }

    public static function setCurrentNotifications($additionalFixFile = null)
    {
        if (!$instance = self::getInstance()) {
            LogHelper::critical("You can off switch off notifications...");
            return false;
        }

        /**
         * Воссстановление из файла (опционального / по-умолчанию)
         */
        if (!file_exists($fixFile = $instance->dumpDirectory . '/' . ($additionalFixFile ?? $instance->fixFile))) {
            LogHelper::warning("Файл фиксации отсутствует... Восстанавливаю из дампа.");
            if (!file_exists($fixFile = $instance->dumpDirectory . '/' . $instance->dumpFile)) {
                throw new SyncException("Файл дампа отсутствует!");
            }
        }

        /**
         * Восстановление из файла фиксации / кэша
         */
        if (!$dump = json_decode(file_get_contents($fixFile), true)) {
            $dump = \Yii::$app->cache->get('currentNotificationParams');
        }

        /**
         * Пуш в планфикс
         */
        if (!$instance->setUsersNotifications($dump)) {
            LogHelper::critical("Установка уведомлений не удалась...");
            return false;
        }

        /**
         * Сброс файла фиксации по-умолчанию
         */
        if (file_exists($fixFile) && !$additionalFixFile) {
            if (!unlink($fixFile)) {
                LogHelper::critical("Не удалось завершить восстановление оповещений...");
                return false;
            }
        }

        LogHelper::success("Восстановление уведомлений прошло успешно!");
        return false;

    }

    /**
     * Зафиксировать текущие параметры уведомлений для одного пользователя (по ID)
     * @param $loginId
     * @return array|bool
     */
    public function fixCurrentNotifications($loginId)
    {
        if (!$currentParams = $this->getUserNotificationParams($loginId)) {
            return false;
        }
        return $this->currentNotificationParams[$loginId] = $this->prepareNotificationParams($currentParams);
    }

    /**
     * Зафиксировать текущие параметры уведомлений для пользователей (users[])
     * @param array $users
     * @param null $additionalFixFile optional file for dump
     * @return array
     * @throws \SyncException
     */
    public function fixUsersCurrentNotifications(array $users, $additionalFixFile = null)
    {
        foreach ($users as $user) {
            if (!isset($user['id'])) {
                continue;
            }
            $this->fixCurrentNotifications($user['id']);
        }

        /**
         * Директория для дампов
         */
        if (!file_exists($this->dumpDirectory) && !mkdir($this->dumpDirectory)) {
            throw new SyncException($this->dumpDirectory . ' can not be written...');
        }

        /**
         * фиксация в опциональный файл
         */
        if ($additionalFixFile) {
            $additionalFixFile = $this->dumpDirectory . '/' . $additionalFixFile;
            if (!file_exists($additionalFixFile) && !touch($additionalFixFile)) {
                throw new SyncException($additionalFixFile . ' can not be written...');
            }
            file_put_contents($additionalFixFile, json_encode($this->currentNotificationParams));
            return $this->currentNotificationParams;
        }

        $dumpFile = $this->dumpDirectory . '/' . $this->dumpFile;
        $fixCurrentFile = $this->dumpDirectory . '/' . $this->fixFile;

        /**
         * Проверка существующей фиксации
         */
        if (file_exists($fixCurrentFile)) {
            LogHelper::critical("Существует фиксация уведомлений! Сначала необходимы выполнить команду 'on-notifications");
            exit();
        }

        /**
         * Фиксация в постоянный дамп
         */
        if (!file_exists($dumpFile) && !touch($dumpFile)) {
            throw new SyncException($dumpFile . ' can not be written...');
        }
        file_put_contents($dumpFile, json_encode($this->currentNotificationParams));

        /**
         * Фиксация в файл по-умолчанию
         */
        if (!file_exists($fixCurrentFile) && !touch($fixCurrentFile)) {
            throw new SyncException($fixCurrentFile . ' can not be written...');
        }
        file_put_contents($fixCurrentFile, json_encode($this->currentNotificationParams));

        return $this->currentNotificationParams;
    }

    /**
     * Получить INT значение уровня оповещений по уровням
     *      planfix | email | browser | sounds
     * @param array $levels
     * @return bool|int
     */
    public function getNotificationLevel($levels = [])
    {
        $totalWeight = 0;
        foreach ($levels as $level) {
            if (!is_string($level)) {
                return false;
            }
            if (!isset($this->planfixWs->notificationsWeights[$level])) {
                return false;
            }

            $totalWeight += (int)$this->planfixWs->notificationsWeights[$level];
        }
        return $totalWeight;
    }

    /**
     * Установить значение уровня оповещений для всех параметров пользователя
     * @param $params
     * @param $level
     * @return mixed
     */
    public function setNotificationsLevel($params, $level)
    {
        foreach ($params as &$param) {
            if (!isset($param['channels'])) {
                continue;
            }

            if (isset($param['type']) && $param['type'] == 'summary') {
                if ($level > 3) {
                    $withoutBrowser = $level - $this->planfixWs->notificationsWeights['browser'];
                    $withoutSounds = $level - $this->planfixWs->notificationsWeights['sounds'];
                    $withoutBrowserAndSounds = $level - $this->planfixWs->notificationsWeights['sounds'] - $this->planfixWs->notificationsWeights['browser'];
                    $param['channels'] = min(abs($withoutBrowserAndSounds), abs($withoutBrowser), abs($withoutSounds));
                } else {
                    $param['channels'] = $level;
                }
                continue;
            } else {
                $param['channels'] = $level;
            }
        }
        return $params;
    }

    /**
     * Установить значение уровня оповещений для всех параметров всех пользователей
     * @param $usersParams
     * @param $level
     * @return mixed
     */
    public function setAllUsersNotificationsLevel($usersParams, $level)
    {
        foreach ($usersParams as $userId => $userParams) {
            $usersParams[$userId] = $this->setNotificationsLevel($userParams, $level);
        }
        return $usersParams;
    }

    /**
     * Форматирование параметров опопвещений для последующей установки
     * @param $params
     * @return array
     */
    public function prepareNotificationParams($params)
    {
        $result = [];
        foreach ($params as $param) {
            $newParam = [];
            foreach ($param as $key => $value) {
                $newParam[strtolower($key)] = $value;
            }
            $result[] = $newParam;
        }
        return $result;
    }

    /**
     * Устнановить параметры уведомлений для массива параметров пользователей
     * @param $usersParams
     * @return bool
     */
    public function setUsersNotifications($usersParams)
    {
        foreach ($usersParams as $userId => $userParams) {
            $this->setUserNotifications($userId, $userParams);
        }
        return true;
    }

    /**
     * Устновить параметры оповещений для пользователя
     * @param $loginId
     * @param $items
     * @return PlanfixWebService
     */
    public function setUserNotifications($loginId, $items)
    {
        $requestData = [
            'command' => 'login:setNoticeParams',
            'login' => $loginId,
            'project' => 0,
            'items' => json_encode($items)
        ];
        $commandResult = $this->planfixWs->sendRequest($requestData);
        return $commandResult;
    }

    /**
     * Получить массив текущих параметров уведомления пользователя
     * [loginid] => ...
     * [projectid] => ...
     * [taskid] => ...
     * [type] => ...
     * [channels] => ...
     * @param $loginId
     * @return bool
     */
    public function getUserNotificationParams($loginId)
    {
        $requestData = [
            'command' => 'login:getNoticeParams',
            'login' => $loginId,
            'project' => 0,
        ];
        $commandResult = $this->planfixWs->sendRequest($requestData);
        $result = isset($commandResult->getBody()['NoticeParams']) ? $commandResult->getBody()['NoticeParams'] : false;
        $result[] = [
            'type' => 'summary',
            'channels' => isset($commandResult->getBody()['SummaryChannels']) ? $commandResult->getBody()['SummaryChannels'] : 0
        ];
        return $result;
    }
}