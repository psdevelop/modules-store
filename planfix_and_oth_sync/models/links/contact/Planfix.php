<?php

namespace app\models\links\contact;

use app\components\enums\ContactTypesEnum;
use app\exceptions\LinkException;
use app\components\PlanfixWebService;
use app\models\links\LinkBase;
use app\models\links\LinkContact;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixCompanies;

/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:06
 */
class Planfix extends LinkContact
{
    /**
     * @var SyncBase[]
     */
    protected $availableTypes = [
        ContactTypesEnum::TYPE_AFFILIATE => SyncPlanfixCompanies::class,
        ContactTypesEnum::TYPE_ADVERTISER => SyncPlanfixCompanies::class
    ];

    public function attributeLabels()
    {
        return [
            'id' => 'ID контакта',
            'type' => 'Тип контакта',
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

    /**
     * @return string
     * @throws LinkException
     */
    public function getContactUrl()
    {
        /**
         * @var $sync SyncBase
         */
        $sync = $this->getContactModel($this->type)::find()
            ->where(['=', $this->getPlatformIdField(), $this->id])
            ->andWhere(['=', 'type', $this->type])
            ->one();
        if (!$sync) {
            LinkException::notFound("Контакт не найден!");
        }
        $planfixId = $sync->planfix_general_id;
        /**
         * @var $planfixWs PlanfixWebService
         */
        $planfixWs = \Yii::$app->planfixWs;
        if (!$planfixAccount = $planfixWs->getConfig('account') ?? null) {
            throw new LinkException("Некорректный аккаунт Planfix", LinkException::ERROR_HEADER_INVALID_QUERY);
        };
        return sprintf($this->planfixContactUrlMask, $planfixAccount, $planfixId);
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
