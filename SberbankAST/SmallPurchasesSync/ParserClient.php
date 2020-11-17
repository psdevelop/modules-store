<?php

namespace App\Service\ETP\SberbankAST\SmallPurchasesSync;

use App\Kernel;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces\IRequest;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\Request\Request;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Class ParserClient
 * @package App\Service\ETP\SberbankAST\SmallPurchasesSync
 * @property $client HttpClient
 * @property Kernel $kernel
 *
 * @author Poltarokov SP
 * @date 06.08.2020
 */
class ParserClient
{

    /**
     * @var HttpClient
     */
    private $httpClient;

    /** @var Kernel $kernel */
    private $kernel;


    /**
     *  Constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;

        $this->httpClient = new HttpClient();
    }

    /**
     * URL-кодирует строку параметров
     * @param $data
     * @param string $keyPrefix
     * @param string $keyPostfix
     * @return string|null
     */
    public function encodePostData(array $data, string $keyPrefix = "", string $keyPostfix = "")
    {
        $vars = null;
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $vars .= data_encode($value, $keyPrefix . $key .
                    $keyPostfix . urlencode("["), urlencode("]"));
            } else {
                $vars .= $keyPrefix . $key . $keyPostfix .
                    "=" . urlencode($value) . "&";
            }
        }
        return $vars;
    }

    /**
     * Выполняет HTTP-запрос
     * @param IRequest $request
     * @return array
     */
    public function sendRequest(IRequest $request): array
    {
        $isPost = $request->getMethod() === 'POST';
        $postFields = $isPost ? $request->getPostFields() : [];

        if ($request->getFormDataType() == Request::URL_ENCODED_FORM) {
            $postFields = $this->encodePostData($postFields);
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => true,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => "spider", // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT => 120,      // timeout on response
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => true,     // Disabled SSL Cert checks
            //CURLOPT_CAINFO => $this->kernel->getProjectDir() . '/sert/server.crt',
            //CURLOPT_CAPATH => "/etc/ssl/certs",
            CURLOPT_POST => $isPost ? 1 : 0,
            CURLOPT_HTTPHEADER => $request->getHeaders(),
            CURLOPT_POSTFIELDS => $postFields,
            //CURLOPT_VERBOSE => true,
        ];

        $ch = curl_init($request->getUrl());
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($content, 0, $headerSize);
        $body = substr($content, $headerSize);

        $error = curl_errno($ch);
        $errorMsg = curl_error($ch);
        curl_close($ch);

        $response = [];
        $response['errno'] = $error;
        $response['errmsg'] = $errorMsg;
        $response['header'] = $header;
        $response['content'] = $body;
        return $response;
    }
}
