<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 07.09.17
 * Time: 12:26
 */

namespace app\exceptions;


use Yii;
use yii\base\Response;
use yii\web\ErrorHandler;


class LinkErrorHandler extends ErrorHandler
{
    /**
     * @param \Exception $exception
     */
    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
        } else {
            $response = new Response();
        }

        if (!$exception instanceof LinkException) {
            $exception = new LinkException($exception->getMessage(), null, [], null, $exception->statusCode ?? 0);
            $response->format = 'json';
        }
        $response->data = $this->convertExceptionToArray($exception);
        $response->send();
    }

    /**
     * @param LinkException $exception
     * @return array
     */
    protected function convertExceptionToArray($exception)
    {
        if (method_exists($exception, 'getHeader')) {
            $exceptionHeader = $exception->getHeader();
        }
        if (method_exists($exception, 'getErrors')) {
            $exceptionErrors = $exception->getErrors();
        }

        return [
            'status' => 'error',
            'errors' => [
                [
                    'header' => $exceptionHeader?? null,
                    'message' => $exception->getMessage(),
                    'fields' => $exceptionErrors ?? null,
                    'code' => $exception->getCode()
                ]
            ],
        ];
    }
}