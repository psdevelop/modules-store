<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 15:16
 */

namespace app\controllers\api;

use app\exceptions\LinkErrorHandler;
use app\controllers\traits\LinkTrait;
use yii\web\Controller;
use yii\web\Response;

class LinkController extends Controller
{
    use LinkTrait;
    protected $params;

    public function init()
    {
        parent::init();
        $handler = new LinkErrorHandler();
        \Yii::$app->set('errorHandler', $handler);
        $handler->register();
    }

    public function beforeAction($action)
    {
        return \Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function afterAction($action, $result)
    {
        return [
            'status' => 'success',
            'data' => $result
        ];
    }

    public function handleModel($model)
    {
        return $this->handleLinkModel($model);
    }
}
