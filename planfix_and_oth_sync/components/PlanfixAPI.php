<?php

namespace app\components;


use app\components\helpers\TimerHelper;
use Exception;
use SimpleXMLElement;
use yii\base\Component;

/**
 * Class PlanfixAPIException
 */
class PlanfixAPIException extends Exception
{
}

/**
 */
class PlanfixAPI extends Component
{

    /**
     * Url that handles API requests
     */
    const API_URL = 'https://api.planfix.ru/xml/';

    /**
     * Version of the library
     */
    const VERSION = '1.0.1';

    /**
     * Maximum size of a page for *.getList requests
     */
    const MAX_PAGE_SIZE = 100;

    /**
     * Default Curl options
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_ENCODING => "gzip",
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
    );

    /**
     * Maximum simultaneous Curl handles in a Multi Curl session
     */
    public static $MAX_BATCH_SIZE = 10;

    /**
     * Api key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Api secret
     *
     * @var string
     */
    protected $apiSecret;

    /**
     * Account name (*.planfix.ru)
     *
     * @var string
     */
    protected $account;

    /**
     * User login
     *
     * @var string
     */
    protected $userLogin;

    /**
     * User password
     *
     * @var string
     */
    protected $userPassword;

    /**
     * Session identifier
     *
     * @var string
     */
    protected $sid;

    public function init()
    {
        parent::init();
    }

    /**
     * Initializes a Planfix Client
     *
     * Required parameters:
     *    - apiKey - Application Key
     *    - apiSecret - Application Secret
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->setApiKey($config['apiKey']);
        $this->setApiSecret($config['apiSecret']);
        $this->setAccount($config['account']);
        if (!$planfixSid = \Yii::$app->cache->get('planfixSid')) {
            $this->setUser(['login' => $config['login'], 'password' => $config['password']]);
            $this->authenticate();
            if ($planfixSid = $this->getSid() && is_string($planfixSid)) {
                \Yii::$app->cache->set('planfixSid', $planfixSid, 24 * 60 * 60);
            }
        }

        $this->setSid($planfixSid);


        parent::__construct();
    }

    /**
     * Set the Api key
     *
     * @param string $apiKey Api key
     * @return PlanfixAPI
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Get the Api key
     *
     * @return string the Api key
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set the Api secret
     *
     * @param $apiSecret
     * @return PlanfixAPI
     */
    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    /**
     * Get the Api secret
     *
     * @return string the Api secret
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * Set the Account
     *
     * @param string $account Account
     * @return PlanfixAPI
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * Get the Account
     *
     * @return string the Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set User Credentials
     *
     * Required parameters:
     *    - login - User login
     *    - password - User password
     *
     * @param array $user The array containing required parameters
     */
    public function setUser($user)
    {
        $this->setUserLogin($user['login']);
        $this->setUserPassword($user['password']);
    }

    /**
     * Set the User login
     *
     * @param string $userLogin User login
     * @return PlanfixAPI
     */
    public function setUserLogin($userLogin)
    {
        $this->userLogin = $userLogin;
        return $this;
    }

    /**
     * Get the User login
     *
     * @return string the User login
     */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * Set the User password
     *
     * @param string $userPassword User password
     * @return PlanfixAPI
     */
    public function setUserPassword($userPassword)
    {
        $this->userPassword = $userPassword;
        return $this;
    }

    /**
     * Get the User password
     * Private for no external use
     *
     * @return string the User password
     */
    private function getUserPassword()
    {
        return $this->userPassword;
    }

    /**
     * Set the Sid
     *
     * @param string $sid Sid
     * @return PlanfixAPI
     */
    public function setSid($sid)
    {
        $this->sid = $sid;
        return $this;
    }

    /**
     * Get the Sid
     *
     * @return string the Sid
     */
    public function getSid()
    {
        return $this->sid;
    }

    /**
     * Authenticate with previously set credentials
     *
     * @throws PlanfixAPIException
     * @return PlanfixAPI
     */
    public function authenticate()
    {
        TimerHelper::timerRun('planfix_auth');
        $userLogin = $this->getUserLogin();
        $userPassword = $this->getUserPassword();

        if (!($userLogin && $userPassword)) {
            throw new PlanfixAPIException('User credentials are not set');
        }

        $requestXml = $this->createXml();

        $requestXml['method'] = 'auth.login';

        $requestXml->login = $userLogin;
        $requestXml->password = $userPassword;
        $requestXml->signature = $this->signXml($requestXml);
        $response = $this->makeRequest($requestXml);
        if (!$response['success']) {
            throw new PlanfixAPIException('Unable to authenticate: '
                . $response['error_str']
                ."\n"
                .json_encode($response,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                ."\n"
                .json_encode($requestXml,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }

        $this->setSid($response['data']['sid']);
        TimerHelper::timerStop('planfix_auth', "Авторизация Planfix API", "PF_API");
        return $this;
    }

    /**
     * Perform Api request
     *
     * @param string|array $method Api method to be called or group of methods for batch request
     * @param array|string $params (optional) Parameters for called Api method
     * @param boolean $asArray
     * @return SimpleXMLElement[]
     * @throws PlanfixAPIException
     */
    public function api($method, $params = '', $asArray = false)
    {
        TimerHelper::timerRun();
        if (!$method) {
            throw new PlanfixAPIException('No method specified');
        } elseif (is_array($method)) {
            if (isset($method['method'])) {
                $params = isset($method['params']) ? $method['params'] : '';
                $method = $method['method'];
            } else {
                foreach ($method as $request) {
                    if (!isset($request['method'])) {
                        throw new PlanfixAPIException('No method specified');
                    }
                }
            }
        }

        $sid = $this->getSid();

        if (!$sid) {
            $this->authenticate();
            $sid = $this->getSid();
        }

        if (is_array($method)) {
            $batch = array();

            foreach ($method as $request) {
                $requestXml = $this->createXml();

                $requestXml['method'] = $request['method'];
                $requestXml->sid = $sid;

                $params = isset($request['params']) ? $request['params'] : '';

                if (is_array($params) && $params) {
                    $this->importParams($params, $requestXml);
                }

                if (!isset($requestXml->pageSize)) {
                    $requestXml->pageSize = self::MAX_PAGE_SIZE;
                }

                $requestXml->signature = $this->signXml($requestXml);

                $batch[] = $requestXml;
            }

            return $this->makeBatchRequest($batch);
        }
        $requestXml = $this->createXml();
        $requestXml['method'] = $method;
        $requestXml->sid = $sid;

        if (is_array($params) && $params) {
            $this->importParams($params, $requestXml);
        }

        if (!isset($requestXml->pageSize)) {
            $requestXml->pageSize = self::MAX_PAGE_SIZE;
        }

        $requestXml->signature = $this->signXml($requestXml);
        $response = $this->makeRequest($requestXml, $asArray);
        TimerHelper::timerStop(null,"PF API ".json_encode($method, JSON_UNESCAPED_UNICODE),"PF");
        return $response;

    }

    /**
     * Create XML request
     *
     * @throws PlanfixAPIException
     * @return SimpleXMLElement the XML request
     */
    protected function createXml()
    {
        $requestXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');
        if (!$account = $this->getAccount()) {
            throw new PlanfixAPIException('Account is not set');
        }

        $requestXml->account = $account;

        return $requestXml;
    }

    /**
     * @param $array
     * @param $xml SimpleXMLElement
     */
    protected function importParams($array, &$xml)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xml->addChild("$key");
                    $this->importParams($value, $subnode);
                } else {
                    $this->importParams($value, $xml);
                }
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    private function append_simplexml(&$simplexml_to, &$simplexml_from)
    {
        foreach ($simplexml_from->children() as $simplexml_child) {
            $simplexml_temp = $simplexml_to->addChild($simplexml_child->getName(), (string)$simplexml_child);
            foreach ($simplexml_child->attributes() as $attr_key => $attr_value) {
                $simplexml_temp->addAttribute($attr_key, $attr_value);
            }

            $this->append_simplexml($simplexml_temp, $simplexml_child);
        }
    }

    /**
     * Sign XML request
     *
     * @param SimpleXMLElement $requestXml The XML request
     * @throws PlanfixAPIException
     * @return string the Signature
     */
    protected function signXml($requestXml)
    {
        return md5($this->normalizeXml($requestXml) . $this->getApiSecret());
    }

    /**
     * Normalize the XML request
     *
     * @param SimpleXMLElement $node The XML request
     * @return string the Normalized string
     */
    protected function normalizeXml($node)
    {
        $node = (array)$node;
        ksort($node);

        $normStr = '';

        foreach ($node as $child) {
            if (is_array($child)) {
                $normStr .= implode('', array_map(array($this, 'normalizeXml'), $child));
            } elseif (is_object($child)) {
                $normStr .= $this->normalizeXml($child);
            } else {
                $normStr .= (string)$child;
            }
        }

        return $normStr;
    }

    /**
     * Make the batch request to Api
     *
     * @param array $batch The array of XML requests
     * @return array the array of Api responses
     */
    protected function makeBatchRequest($batch)
    {
        $mh = curl_multi_init();

        $batchCnt = count($batch);
        $max_size = $batchCnt < self::$MAX_BATCH_SIZE ? $batchCnt : self::$MAX_BATCH_SIZE;

        $batchResult = array();

        for ($i = 0; $i < $max_size; $i++) {
            $requestXml = array_shift($batch);
            $ch = $this->prepareCurlHandle($requestXml);
            $chKey = (string)$ch;
            $batchResult[$chKey] = array();
            curl_multi_add_handle($mh, $ch);
        }

        do {
            do {
                $mrc = curl_multi_exec($mh, $running);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($request = curl_multi_info_read($mh)) {
                $ch = $request['handle'];
                $chKey = (string)$ch;
                $batchResult[$chKey] = $this->parseApiResponse(curl_multi_getcontent($ch), curl_error($ch));

                if (count($batch)) {
                    $requestXml = array_shift($batch);
                    $ch = $this->prepareCurlHandle($requestXml);
                    $chKey = (string)$ch;
                    $batchResult[$chKey] = array();
                    curl_multi_add_handle($mh, $ch);
                }

                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }

            if ($running) {
                curl_multi_select($mh);
            }

        } while ($running && $mrc == CURLM_OK);

        return array_values($batchResult);
    }

    /**
     * Make the request to Api
     *
     * @param SimpleXMLElement $requestXml The XML request
     * @param $asArray boolean result as array
     * @return array the Api response
     */
    protected function makeRequest($requestXml, $asArray = false)
    {
        TimerHelper::timerRun();
        $ch = $this->prepareCurlHandle($requestXml);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        TimerHelper::timerStop(null, "Execute planfix http-request");
        return $this->parseApiResponse($response, $error, $asArray);
    }

    /**
     * Prepare the Curl handle
     *
     * @param SimpleXMLElement $requestXml The XML request
     * @return resource the Curl handle
     */
    protected function prepareCurlHandle($requestXml)
    {
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, self::$CURL_OPTS);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getApiKey() . ':X');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestXml->asXML());

        return $ch;
    }

    /**
     * Parse the Api response
     *
     * @link http://goo.gl/GWa1c List of Api error codes
     *
     * @param string $response The Api response
     * @param string $error The Curl error if any
     * @param boolean $asArray
     * @return array the Curl handle
     * @throws PlanfixAPIException
     */
    protected function parseApiResponse($response, $error, $asArray)
    {
        $result = array(
            'success' => 1,
            'error_str' => '',
            'meta' => null,
            'data' => null
        );

        if (!$response && !$error) {
            throw new PlanfixAPIException('Api service is not available.');
        }


        if ($error) {
            $result['success'] = 0;
            $result['error_str'] = $error;
            return $result;
        }

        try {
            $responseXml = new SimpleXMLElement($response);
        } catch (Exception $e) {
            $result['success'] = 0;
            $result['error_str'] = $e->getMessage();
            return $result;
        }

        if ((string)$responseXml['status'] == 'error') {
            $result['success'] = 0;
            $result['error_str'] = 'Code: ' . $responseXml->code . ' / Message: ' . $responseXml->message;
            $result['error_code'] = (string)$responseXml->code;
            $result['message'] = (string)$responseXml->message;
            return $result;
        }

        if (isset($responseXml->sid)) {
            $result['data']['sid'] = (string)$responseXml->sid;
        } else {
            $responseXml = $responseXml->children();

            foreach ($responseXml->attributes() as $key => $val) {
                $result['meta'][$key] = (int)$val;
            }

            if ($result['meta'] == null || $result['meta']['totalCount'] || $result['meta']['count']) {
                if ($asArray) {
                    $result['data'] = $this->exportData($responseXml);
                } else {
                    $result['data'] = $responseXml;
                }
            }
        }

        return $result;
    }

    /**
     * Конвертирование API-response в массив
     * @param $responseXml
     * @return array
     */
    protected function exportData($responseXml)
    {
        return $this->toArray($responseXml);
    }

    /**
     * Конвертирование любого объекта в массив
     * @param $obj
     * @return array
     */
    public function toArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }
        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $new[$key] = $this->toArray($val);
            }
        } else {
            $new = $obj;
        }
        return $new;
    }
}
