<?php

namespace app\models\links;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class LinkContact extends LinkModel
{
    public $id;
    public $type;
    public $platform;

    protected static $entityNamespace = 'contact';
}
