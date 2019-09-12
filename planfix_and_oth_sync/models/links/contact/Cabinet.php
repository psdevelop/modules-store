<?php

namespace app\models\links\contact;

use app\exceptions\LinkException;
use app\models\cabinet\CabinetBase;
use app\models\links\LinkBase;
use app\models\links\LinkContact;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixUsers;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class Cabinet extends LinkContact
{
    /**
     * @var SyncBase[]
     */
    protected $availableTypes = [
        'user' => SyncPlanfixUsers::class,
        'contact' => SyncPlanfixCompanies::class,
    ];

    public function attributeLabels()
    {
        return [
            'id' => 'ID контакта',
            'type' => 'Тип контакта (' . implode(' / ', array_keys($this->availableTypes)) . ')',
            'platform' => 'Платформа (' . implode(' / ', array_keys($this->platformSynonyms)) . ')',
        ];
    }

    public function rules()
    {
        return array_merge([
            [['id', 'type', 'platform'], 'required', 'message' => 'Не задан необходимый параметр "{attribute}"'],
            ['type', 'in', 'range' => array_keys($this->availableTypes), 'message' => 'Недопустимое значение "{attribute}"'],
            ['platform', 'in', 'range' => array_keys($this->platformSynonyms), 'message' => 'Недопустимое значение "{attribute}"'],
        ], parent::rules());
    }

    public function getHelpView()
    {
        return "partials/link-contact_help";
    }

    public function urlHandler()
    {
        return $this->getContactUrl();
    }

    public function gotoHandler()
    {
        $url = $this->getContactUrl();
        if ($this->module == LinkBase::MODULE_API) {
            return $url;
        }
        return \Yii::$app->response->redirect($url);
    }

    public function getContactUrl()
    {
        $planfixId = $this->id;
        if (!$syncModel = $this->getAvailableType($this->type)) {
            throw new LinkException(
                "Некорректный тип контакта Planfix - $this->type",
                LinkException::ERROR_HEADER_INVALID_QUERY
            );
        }

        if (!$syncObject = $syncModel::findOne(['planfix_general_id' => $planfixId])) {
            LinkException::notFound("Контакт не найден!");
        };

        /**
         * @var $cabinetObjectId CabinetBase
         */
        if (!$cabinetObjectId = $syncObject->{$this->getPlatformIdField()} ?? null) {
            LinkException::notFound("Контакт не найден!");
        }

        return sprintf($this->cabinetContactUrlMask, "manager.romanb.leads", $this->getCabinetContactType($syncObject->type) . "s", $cabinetObjectId);
    }

    /**
     * @param $type
     * @return SyncBase
     * @throws \Exception
     */
    public function getContactModel($type)
    {
        if (!$contactModelClass = $this->getAvailableType($type)) {
            throw new LinkException("Некорректо указан тип контакта", LinkException::ERROR_HEADER_INVALID_QUERY);
        }
        return $contactModelClass;
    }

    /**
     * @param $typeKey
     * @return SyncBase|null
     */
    public function getAvailableType($typeKey)
    {
        return $this->availableTypes[$typeKey] ?? null;
    }
}
