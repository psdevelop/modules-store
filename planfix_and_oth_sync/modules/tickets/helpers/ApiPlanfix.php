<?php

namespace app\modules\tickets\helpers;

use Spatie\ArrayToXml\ArrayToXml;
use yii\httpclient\Client;

class ApiPlanfix
{
    const METHOD_LOGIN = 'auth.login';

    const METHOD_TASK_LIST = 'task.getList';
    const METHOD_TASK_ADD = 'task.add';
    const METHOD_TASK_GET = 'task.get';
    const METHOD_TASK_UPDATE = 'task.update';
    const METHOD_TASK_GET_MULTI = 'task.getMulti';

    const METHOD_CONTACT_GET = 'contact.get';

    const METHOD_ACTION_ADD = 'action.add';
    const METHOD_ACTION_GET = 'action.get';
    const METHOD_ACTION_GET_LIST = 'action.getList';
    const METHOD_ACTION_UPDATE = 'action.update';

    const RESPONSE_OK = 'ok';

    /** @var Client */
    private $httpClient;

    /** @var string[] */
    private $headers;

    /** @var string */
    private static $sid = null;

    public function __construct(Client $client)
    {
        $this->httpClient = $client;
        $this->headers = [
            'Content-Type' => 'application/xml',
            'Authorization' => 'Basic ' . env('AUTH_BASIC'),
        ];
    }

    public function sendRequest(string $method, array $fields = [])
    {
        $fields = array_merge(
            [
                'account' => env('PLANFIX_ACCOUNT'),
                'sid' => $this->getSid(),
            ],
            $fields
        );

        $xml = $this->buildRequestApi($method, $fields);

        $response = $this->httpClient->createRequest()
            ->setUrl(env('PLANFIX_URL_API'))
            ->setMethod('POST')
            ->setFormat(Client::FORMAT_XML)
            ->addHeaders($this->headers)
            ->setContent($xml)
            ->send();

        return $response->getData();
    }

    public function isResponseOk($response): bool
    {
        return $response['@attributes']['status'] === self::RESPONSE_OK;
    }

    private function buildRequestApi(string $method, array $fields = []): string
    {
        $xml = ArrayToXml::convert(
            $fields,
            [
                'rootElementName' => 'request',
                '_attributes' => ['method' => $method],
            ]
        );

        return $xml;
    }

    private function getSid(): string
    {
        if (self::$sid === null) {
            $fields = [
                'account' => env('PLANFIX_ACCOUNT'),
                'login' => env('PLANFIX_LOGIN'),
                'password' => env('PLANFIX_PASSWORD'),
            ];

            $xml = $this->buildRequestApi(self::METHOD_LOGIN, $fields);

            $response = $this->httpClient->createRequest()
                ->setUrl(env('PLANFIX_URL_API'))
                ->setMethod('POST')
                ->setFormat(Client::FORMAT_XML)
                ->addHeaders($this->headers)
                ->setContent($xml)
                ->send();

            $responseBody = $response->getData();

            self::$sid = $responseBody['sid'];
        }

        return self::$sid;
    }
}