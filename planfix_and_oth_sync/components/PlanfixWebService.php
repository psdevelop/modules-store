<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 07.04.17
 * Time: 18:22
 */

namespace app\components;

use app\components\helpers\LogHelper;
use app\components\helpers\TimerHelper;
use Exception;
use Yii;
use yii\base\Component;

class PlanfixWebService extends Component
{

    public static $CURL_OPTS = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0
    ];
    protected static $apiUrl;
    protected static $service;
    protected static $url;
    protected $config;
    protected $account;
    protected $login;
    protected $password;
    protected $cookie;
    protected $client;
    protected $response;

    public function __construct(array $config = [])
    {
        TimerHelper::timerRun();
        Yii::$app->cache->delete($this->cacheCookieKey());
        $this->setConfigParams($config);
        self::$url = $this->getUrl();
        $this->auth();
        parent::__construct($config);
        TimerHelper::timerStop(null, "WS INIT", "W-S");
    }

    protected function setConfigParams($config)
    {
        $this->setConfig($config);
        foreach ($this->config as $key => $value) {
            $this->{'set' . ucfirst($key)}($value);
        }
    }

    public function getConfig($key = null)
    {
        return $key ? ($this->config[$key] ?? null) :$this->config;
    }

    /* SETTERS */

    /**
     * Авторизация (одиночка в кэше)
     * @return bool|string
     * @throws PlanfixWebServiceException
     */
    public function auth()
    {
        // Если куки в кэше, то не авторизуемся
        if ($this->getCookiesFromCache()) {
            return true;
        }

        // В противном случае, авторзуемся и ловим куки
        $response = $this->sendRequest([
            'command' => 'logon:auth',
            'tbUserName' => $this->login,
            'tbUserPassword' => $this->password,
            'cbUserRemember' => 1
        ])->getBody();

        // Если не прошла авторизация
        if (!isset($response['Result']) && $response['Result'] == 'success') {
            throw new PlanfixWebServiceException('Неудачная авторизация...');
        }
        return $this->catchCookies();

    }

    /**
     * Взять куки из кэша
     * @return bool|mixed
     */
    public function getCookiesFromCache()
    {
        if ($this->cookie = Yii::$app->cache->get($this->cacheCookieKey())) {
            return $this->cookie;
        }
        return false;
    }

    /**
     * Ключ для куки авторизации
     * @return string
     */
    protected function cacheCookieKey()
    {
        return 'planfix_ws_' . date('Y_m_d', time());
    }

    /**
     * Тело ответа
     * @return bool|string
     */
    public function getBody()
    {
        if (!$this->response) {
            return null;
        }
        $info = curl_getinfo($this->client);
        $start = $info['header_size'];
        $response = $this->response->full;
        $bodyStr = substr($response, $start, strlen($response) - $start);
        return $this->response->body = json_decode($bodyStr, true);
    }

    /**
     * Отправка запроса
     * @param $fields
     * @return $this
     */
    public function sendRequest($fields)
    {
        TimerHelper::timerRun();
        $this->prepareClient(self::$url);
        curl_setopt($this->client, CURLOPT_POSTFIELDS, http_build_query($fields));
        $this->response = (object)[];
        TimerHelper::timerRun();
        $this->response->full = curl_exec($this->client);
        $this->getBody();
        TimerHelper::timerStop(null, "WS CURL EXEC", "W-S");
        TimerHelper::timerStop(null, "WS HTTP", "W-S");
        return $this;
    }

    /**
     * Подготовка клиента cUrl
     * @param $url
     */
    protected function prepareClient($url)
    {
        $this->client = curl_init($url);
        curl_setopt_array($this->client, self::$CURL_OPTS);
        curl_setopt($this->client, CURLOPT_POST, 1);
        curl_setopt($this->client, CURLOPT_HEADER, true);
        curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->client, CURLOPT_COOKIE, $this->getCookiesFromCache());
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
    }


    /* GETTERS */

    public function getUrl()
    {
        return $this->getServiceUrl() . 'ajax/';
    }

    public function getServiceUrl()
    {
        return 'https://' . $this->account . '.planfix.ru/';
    }

    /**
     * Поймать куки после авторизации
     * @return string
     */
    public function catchCookies()
    {
        $content = $this->response->full;
        $regexPattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
        preg_match_all($regexPattern, $content, $matches);
        $cookiesOut = implode("; ", $matches['cookie']);
        return $this->setCookiesToCache($cookiesOut);
    }

    /**
     * Запомнить куки
     * @param $cookies
     * @return mixed
     */
    protected function setCookiesToCache($cookies)
    {
        $this->cookie = $cookies;
        Yii::$app->cache->set($this->cacheCookieKey(), $this->cookie = $cookies, 60 * 60 * 24);
        return $cookies;
    }

    public function init()
    {
        parent::init();
    }

    /**
     * Установка доступа контакта
     * @param $params
     */
    public function updateContactCard($params)
    {
        $this->sendRequest(
            $this->prepareRequest('task:saveFull', $params, [
                'TaskWorkersUsers',
                'TaskWorkersGroups',
                'TaskMembersUsers',
                'TaskMembersGroups',
                'TaskAuditorsUsers',
                'TaskAuditorsGroups',
                'TaskOwnerID',
                'id',
            ])
        );
    }

    /**
     * Подготовка тела запроса
     * @param array $params
     * @param array $model
     * @return array
     */
    public function prepareRequest($command, array $params, array $model = [])
    {
        if (!$model) {
            $model = array_keys($params);
        }
        $out = [];
        $model[] = 'command';
        $params['command'] = $command;
        foreach ($params as $key => $value) {
            if (!in_array($key, $model)) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(';', $value);
            }

            $out[$key] = $value;
        }
        return $out;
    }

    /**
     * Изменить ответственных
     * @param $taskId
     * @param array $users
     * @param array $groups
     */
    public function changeWorkers($taskId, array $users = [], array $groups = [])
    {
        foreach ($groups as &$group) {
            $group = '-' . $group;
        }

        $params = [
            'task' => $taskId,
            'EmployedUsers' => array_merge($users, $groups)
        ];

        $this->sendRequest(
            $this->prepareRequest('task:changeWorkers', $params, array_keys($params))
        );
    }

    /**
     * Изменить тех, кто может видеть контакт
     * @param $taskId
     * @param array $users
     * @param array $groups
     */
    public function changeMembers($taskId, array $users = [], array $groups = [])
    {
        foreach ($groups as &$group) {
            $group = '-' . $group;
        }

        $params = [
            'task' => $taskId,
            'TaskMembers' => array_merge($users, $groups)
        ];

        $this->sendRequest(
            $this->prepareRequest('task:changeMembers', $params, array_keys($params))
        );
    }

    /**
     * Изменить тех, кто может видеть контакт
     * @param $taskId
     * @param array $users
     * @param array $groups
     */
    public function changeAuditors($taskId, array $users = [], array $groups = [])
    {
        foreach ($groups as &$group) {
            $group = '-' . $group;
        }

        $params = [
            'task' => $taskId,
            'TaskAuditors' => array_merge($users, $groups)
        ];

        $this->sendRequest(
            $this->prepareRequest('task:changeAuditors', $params, array_keys($params))
        );
    }

    /**
     * ID Задачи по контакту
     * @param $contactId
     * @return array
     */
    public function getTaskIdByContact($contactId)
    {
        return $this->getTaskByContact($contactId)['task']['ID'] ?? null;
    }

    /**
     * GeneralID Задачи по контакту
     * @param $contactId
     * @return array
     */
    public function getTaskGeneralIdByContact($contactId)
    {
        return $this->getTaskByContact($contactId)['task']['GeneralID'] ?? null;
    }

    /**
     * GeneralID Задачи по контакту
     * @param $contactId
     * @return array
     */
    public function getTaskLoginIdByContact($contactId)
    {
        return $this->getTaskByContact($contactId)['LoginID'] ?? null;
    }

    /**
     * Задача по контакту
     * @param $contactId
     * @return mixed|null
     */
    public function getTaskByContact($contactId)
    {
        $fullContactData = $this->sendRequest([
            'command' => 'contact:getByContactId',
            'contact' => $contactId,
        ])->getBody();

        if (!isset($fullContactData['Contacts'])) {
            return null;
        }

        if (empty($fullContactData['Contacts'])) {
            return null;
        }

        return array_shift($fullContactData['Contacts']);
    }

    /**
     * Ссылка на запрос для кабинета
     * @param $planfixTask
     * @return null|string
     */
    public function getTaskNote($planfixTask)
    {
        if (!isset($planfixTask['general'])) {
            LogHelper::warning("У задачи отсутсвует поле 'general'");
            return null;
        }
        if (!isset($planfixTask['note'])) {
            LogHelper::warning("У задачи отсутсвует поле 'note' - создаем заглушку");
            $planfixTask['note'] = " ";
        }

        // Формируем заметку для кабинета
        return '<a href="' . $this->getServiceUrl() . 'task/' . $planfixTask['general'] . '"> ' . $planfixTask['general'] . ' </a>' . ($planfixTask['note'] ?? " ");

    }

    protected function setConfig($config)
    {
        $this->config = $config;
    }

    protected function getAccount()
    {
        return $this->account;
    }

    protected function setAccount($account)
    {
        $this->account = $account;
    }

    protected function getLogin()
    {
        return $this->login;
    }

    protected function setLogin($login)
    {
        $this->login = $login;
    }

    protected function getPassword()
    {
        return $this->password;
    }

    protected function setPassword($password)
    {
        $this->password = $password;
    }

    protected function getResponse()
    {
        return $this->response;
    }

    protected function setResponse($response)
    {
        $this->response = $response;
    }

    protected function setNotificationsWeights($value)
    {
        $this->notificationsWeights = $value;
    }
}

/**
 * Class PlanfixAPIException
 */
class PlanfixWebServiceException extends Exception
{
}
