<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 15:16
 */

namespace app\controllers\service;

use app\controllers\traits\LinkTrait;

use yii\web\Controller;

class LinkController extends Controller
{
    use LinkTrait;
    protected $params;

    public function handleModel($model)
    {
        return $this->render('index', [
            'data' => $this->handleLinkModel($model)
        ]);
    }
}
