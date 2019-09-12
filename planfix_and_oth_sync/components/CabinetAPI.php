<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 04.07.17
 * Time: 17:58
 */

namespace app\components;


use yii\base\Component;
use yii\base\Exception;

class CabinetAPI extends Component
{
    protected $base;
    protected $config;

    protected $apiKey;
    protected $apiUrl;

    protected $component;
    protected $method;

    protected $client;
    protected $response;
    protected $request;

    public $error;

    protected static $CURL_OPTS = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
    ];

    /**
     * CabinetAPI constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Запуск Leads API / Trade API
     * @param $base string leads/trade
     * @return $this
     */
    public function run($base)
    {
        $this->setBase($base);
        $this->setApiKey($this->config[$base . 'ApiKey']);
        $this->setUrl($this->config[$base . 'ApiUrl']?? null);
        return $this;
    }

    /**
     * @param $base
     * @return $this
     */
    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    /**
     * @return null
     */
    public function getBase()
    {
        return $this->base ?? null;
    }

    /**
     * Set the Api key
     * @param $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Get the Api key
     * @return mixed|null
     */
    protected function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set the Api Url
     * @param string $url Url
     * @return self
     */
    public function setUrl($url)
    {
        $this->apiUrl = $url;
        return $this;
    }

    /**
     * Get the Api Url
     * @return mixed
     */
    public function getUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param $componentName
     * @return $this
     */
    public function setComponent($componentName)
    {
        $this->component = $componentName;
        return $this;
    }

    /**
     * @param $methodName
     * @return $this
     */
    public function setMethod($methodName)
    {
        $this->method = $methodName;
        return $this;
    }

    /**
     * @return string
     */
    protected function queryEndPoint()
    {
        return $this->apiUrl . '/' . $this->component . '/' . $this->method;
    }

    /**
     * Отправка запроса
     * @param $id
     * @param $fields
     * @return $this
     * @throws Exception
     */
    public function call($id, $fields)
    {
        if (!$base = $this->getBase()) {
            throw new Exception("Необходимо определить базу для запросов! leads/trade");
        }

        $this->prepareClient($this->queryEndPoint() . '?' . http_build_query(['id' => $id, 'token' => $this->getApiKey()]));
        curl_setopt($this->client, CURLOPT_POSTFIELDS, $fields);
        $this->request = $fields;
        $this->response = (object)[];
        $this->response->full = curl_exec($this->client);
        $this->getBody();
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
        curl_setopt($this->client, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
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
     * Ошибка
     * @return bool|object
     */
    public function getError()
    {
        $result = $this->response->body;
        if ($isError = ($result['status'] === 'error')) {
            $this->error = (object)[];
            $errorData = $result['error'] ?? null;
            $this->error->errorType = $errorData['type'] ?? null;
            $this->error->errorParams = $errorData['params'] ?? [];
        }
        return $this->error ?? false;
    }

    /**
     * Запрос
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Ответ
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }


    /**
     * Обновление запроса на срочную выплату
     * @param $id
     * @param $status
     * @param $comment
     * @return bool|string
     */
    public function updateBillingRequest($id, $status, $comment)
    {
        $this->setComponent('billing');
        $this->setMethod('updateRequest');
        $this->call(
            (int)$id,
            [
                'status' => (string)$status,
                'employee_note' => (string)$comment,
            ]);
        return $this->getBody();
    }

    /**
     * Сменить статус подключения трекингрового домента
     * @param $id
     * @param $status
     * @return bool|string
     */
    public function updateOffersToTrackingDomainStatus($id, $status)
    {
        $this->setComponent('offerToTrackingDomain');
        $this->setMethod('changeStatus');
        $this->call(
            (int)$id,
            [
                'id' => (int)$id,
                'status' => (string)$status,
            ]
        );
        return $this->getBody();
    }

    /**
     * Сменить комментарий подключения трекингрового домента
     * @param $id
     * @param $comment
     * @return bool|string
     */
    public function updateOffersToTrackingDomainComment($id, $comment)
    {
        $this->setComponent('offerToTrackingDomain');
        $this->setMethod('changeComment');
        $this->call(
            (int)$id,
            [
                'id' => (int)$id,
                'comment' => (string)$comment,
            ]
        );
        return $this->getBody();
    }

    /**
     * Согласовать ачивку
     * @param $achieveId
     * @return bool|string
     */
    public function achievementReward($achieveId)
    {
        $this->setComponent('achievements')->setMethod('reward')->call(
            (int)$achieveId,
            [
                'id' => (int)$achieveId,
            ]
        );
        return $this->getBody();
    }

    /**
     * Отклонить ачивку
     * @param $achieveId
     * @return bool|string
     */
    public function achievementReject($achieveId)
    {
        $this->setComponent('achievements')->setMethod('reject')->call(
            (int)$achieveId,
            [
                'id' => (int)$achieveId,
            ]
        );
        return $this->getBody();
    }

    /**
     * Смена статуса тикета через API
     * @param $id integer
     * @param $status string
     * @return bool|string
     */
    public function updateTicketStatus($id, $status)
    {
        $this->setComponent('tickets');
        $this->setMethod('changeStatus');
        $this->call(
            (int)$id,
            [
                'id' => (int)$id,
                'status' => (string)$status,
            ]
        );
        return $this->getBody();
    }

    /**
     * Смена id задачи в Planfix в тикете через API
     * @param $id integer
     * @param $planfixTaskId integer
     * @return bool|string
     */
    public function updateTicketPlanfixTaskId($id, $planfixTaskId)
    {
        $this->setComponent('tickets');
        $this->setMethod('changePlanfixId');
        $this->call(
            (int)$id,
            [
                'id' => (int)$id,
                'planfix_task_id' => (string)$planfixTaskId,
            ]
        );
        return $this->getBody();
    }
}