<?php

namespace app\models;

use yii\web\IdentityInterface;
use yii\web\MethodNotAllowedHttpException;

class User implements IdentityInterface
{
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if (env('API_AUTH_TOKEN_FOR_LEADS') !== $token) {
            throw new MethodNotAllowedHttpException();
        }

        return new self();
    }


    public static function findIdentity($id)
    {
        // TODO: Implement findIdentity() method.
    }


    public function getId()
    {
        // TODO: Implement getId() method.
    }

    public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
    }

    public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
    }
}