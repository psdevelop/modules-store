<?php

namespace app\models\planfix;

use app\components\CabinetAPI;
use app\components\helpers\LogHelper;
use app\components\helpers\PlanfixHelper;
use app\components\helpers\TimerHelper;
use app\components\PlanfixAPI;
use app\components\PlanfixWebService;
use app\exceptions\SyncException;
use app\models\cabinet\CabinetAccountNote;
use app\models\cabinet\CabinetBase;
use app\models\cabinet\CabinetChat;
use app\models\cabinet\CabinetChatMessage;
use app\models\cabinet\CabinetCompany;
use app\models\cabinet\CabinetCompanyUser;
use app\models\cabinet\CabinetEmployee;
use app\models\cabinet\CabinetOffer;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixChats;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixNotes;
use app\models\sync\SyncPlanfixOffers;
use app\models\sync\SyncPlanfixPayments;
use app\models\sync\SyncPlanfixUnknownUsers;
use Yii;

/**
 * Bengraf Roman <rb@leads.su> - LEADS.SU
 * Date: 29.03.2017
 */
class PlanfixBase
{
    const ADVERTISER_COMPANY = 'ADVERTISER_COMPANY';
    const WEBMASTER_COMPANY = 'WEBMASTER_COMPANY';
    const ADVERTISER_USER = 'ADVERTISER_USER';
    const WEBMASTER_USER = 'WEBMASTER_USER';
    /**
     * Размер страницы выборки для Planfix
     * @var int
     */
    public static $PAGE_SIZE = 100;
    /**
     * Инстанс для статических методов
     * @var self
     */
    public static $instances = [];
    public static $modifiedField = 'modified';
    public static $createdField = 'date_added';
    /**
     * Наименование объекта Planfix
     * @var string
     */
    public $objectName;
    /**
     * Primary Key объекта Planfix
     * @var string
     */
    public $objectKey;
    /**
     * Состав полей объекта Planfix
     * @var
     */
    public $fields;
    /**
     * Инстанс объекта Planfix
     * @var
     */
    public $object;
    /**
     * ID объекта Planfix
     * @var
     */
    public $id;

    /**
     * Ошибка объекта
     * @var
     */
    public $error;

    /**
     * Время жизни кэша в секундах
     * @var int
     */
    public $cacheDuration = 60 * 60 * 24;

    /**
     * @var array
     */
    public $customFields;

    /**
     * @var
     */
    public $customStatuses;

    /**
     * Системные значения по-молчанию
     * @var
     */
    public $defaults;

    /**
     * @var array
     */
    public $filters;

    /**
     * @var array
     */
    public $templates;

    /**
     * @var array
     */
    public $projects;

    /**
     * @var array
     */
    public $handbooks;

    /**
     * Компании Planfix
     * @var array
     */
    public $planfixCompanies;
    /**
     * Ключ кэша для компаний
     * @var
     */
    public $companiesCacheKey;
    /**
     * Контакты Planfix
     * @var array
     */
    public $planfixContacts;
    /**
     * Ключ кэша для контактов
     * @var
     */
    public $contactsCacheKey;
    /**
     * Пользователи Planfix
     * @var array
     */
    public $planfixUsers;

    /**
     * Конфиг
     * @var string
     */
    public $configName = 'planfix';
    /**
     * Маппинг групп
     * @var array
     */
    public $groups;

    /**
     * Данные по кабинетам
     * @var array
     */
    public $cabinets;

    /**
     * Маппинг неизвестных пользователей leads / trade
     * @var array
     */
    public $phantoms;

    /**
     * Инстанс PlanfixAPI
     * @var PlanfixAPI
     */
    protected $planfixApi;

    /**
     * Инстанс CabinetAPI
     * @var CabinetAPI
     */
    protected $cabinetApi;

    /**
     * Инстанс PlanfixWebService
     * @var PlanfixWebService
     */
    protected $planfixWs;

    /**
     * PlanfixBase constructor. | Init API |
     */
    public function __construct()
    {
        $this->config = $this->getConfig('config', []);
        foreach ($this->config as $configName => $config) {
            $this->{$configName} = $config;
        }

        $class = static::class;

        $this->planfixWs = Yii::$app->planfixWs;
        $this->planfixApi = Yii::$app->planfixApi;
        $this->cabinetApi = Yii::$app->cabinetApi;
        if (!isset(static::$instances[$class]) || !static::$instances[$class] instanceof static) {
            static::$instances[$class] = $this;
        }
    }

    /**
     * Получить конфиг
     * @param $key
     * @param null $default
     * @return array | null
     */
    public function getConfig($key, $default = null)
    {
        return isset(Yii::$app->params[$this->configName][$key]) ? Yii::$app->params[$this->configName][$key] : $default;
    }

    public function getCustomStatusId($key)
    {
        if(!$customStatuses = PlanfixBase::instance()->config['customStatuses']){
            return null;
        }

        return $customStatuses[$key]['id'] ?? null;

    }

    public function getCustomStatusRuValue($key)
    {
        if(!$customStatuses = PlanfixBase::instance()->config['customStatuses']){
            return null;
        }

        return $customStatuses[$key]['ruValue'] ?? null;

    }

    public static function getHandbook($id, $by = null, $find = null)
    {
        $response = static::instance()->planfixApi->api('analitic.getHandbook', [
            'handbook' => [
                'id' => $id
            ]
        ], true);

        if (!isset($response['data']['records']['record'])) {
            return [];
        }

        $records = $response['data']['records']['record'];
        $stack = [];
        $out = [];
        foreach ($records as $index => $record) {
            foreach ($record['value'] as $attribute) {
                $stack[$index][$attribute['@attributes']['name']] = $attribute['@attributes'];
            }
        }

        foreach ($stack as $item) {
            if (isset($item[$by])) {
                $out[$item[$by]['value']] = $item;
            } else {
                $out[] = $item;
            }
        }

        if ($find) {
            if (isset($out[$find])) {
                return $out[$find];
            }
            return array_shift($out);
        }

        return $out;
    }

    /**
     * @param $object CabinetBase
     * @param $key
     * @return null
     */
    public function getTemplate($object, $key)
    {
        return $this->config['templates'][$key] ?? null;
    }

    /**
     * Получить один контакт
     * @param $conditions
     * @param $objectName
     * @return array
     */
    public static function findOne($conditions = [], $objectName = null)
    {
        $self = static::instance();
        if (!$objectName) {
            $objectName = $self->objectName;
        }

        $response = $self->planfixApi->api($objectName . '.get', [
            $objectName => $conditions
        ], true);

        $objectArray = $response['data'][$objectName];
        $objectFromProperties = [];
        if (!isset($self->fields)) {
            return [];
        }
        foreach ($self->fields as $property) {
            $objectFromProperties[$property] = isset($objectArray[$property]) ? $objectArray[$property] : null;
        }
        return $objectFromProperties;
    }

    /**
     * Инстанс соотв. класса модели Планфикс
     * @return static
     */
    public static function instance()
    {
        $class = static::class;
        if (!isset(static::$instances[$class]) || !static::$instances[$class] instanceof static) {
            static::$instances[$class] = new static();
        }
        return static::$instances[$class];
    }

    /**
     * Удаление объекта Planfix
     * @return array
     */
    public function delete()
    {
        return $this->planfixApi->api(
            $this->objectName . '.delete',
            [
                $this->objectName => [
                    'id' => $this->id
                ]
            ],
            true
        );
    }

    /**
     * @param $object
     * @param null $map
     * @return array|bool
     */
    public function prepareCustomStandard($object, $map = null)
    {
        if (!$map) {
            $map = 'customContactMap';
        }

        if (!isset(PlanfixCustomData::instance()->{$map})) {
            return [];
        }

        return PlanfixCustomData::instance()->prepareCustomFields(
            $object,
            PlanfixCustomData::instance()->{$map}
        );
    }

    /**
     * Получить все контакты Планфикс и взять как свойство
     */
    public function getAllPlanfixContacts()
    {
        // Get Planfix Contacts
        $params = [
            'target' => 'company',
        ];
        $this->companiesCacheKey = $this->objectName . '_' . json_encode($params);
        $this->planfixCompanies = PlanfixContact::find(
            $params,
            $this->objectName,
            $this->objectKey,
            $this->companiesCacheKey,
            'id'
        );


        $params = [
            'target' => 'contact',
        ];
        $this->contactsCacheKey = $this->objectName . '_' . json_encode($params);
        // Get Planfix Contacts
        $this->planfixContacts = PlanfixContact::find(
            [
                'target' => 'contact',
            ],
            $this->objectName,
            $this->objectKey,
            $this->contactsCacheKey,
            'id'
        );
    }

    /**
     * Получение списка объектов Planfix
     * @param array $conditions
     * @param null $objectName
     * @param null $objectKey
     * @param null $cacheKey
     * @param null $itemKey
     * @return array|mixed
     */
    public static function find($conditions = [], $objectName = null, $objectKey = null, $cacheKey = null, $itemKey = null)
    {
        $self = static::instance();
        if (!$objectName) {
            $objectName = $self->objectName;
        }
        if (!$objectKey) {
            $objectKey = $self->objectKey;
        }

        $api = $self->planfixApi;

        $planfixObjects = [];


        if ((bool)$cacheKey !== false && $planfixObjects = Yii::$app->cache->get($cacheKey)) {
            return $planfixObjects;
        }

        $method = sprintf('%s.getList', $objectName);

        $xmlResponse = $api->api($method, $conditions, true);
        $totalCount = $xmlResponse['meta']['totalCount'];

        for ($i = 1; $i <= ceil($totalCount / self::$PAGE_SIZE); $i++) {
            $conditions = array_merge($conditions, ['pageCurrent' => $i, 'pageSize' => self::$PAGE_SIZE]);
            $xmlResponse = $api->api($method, $conditions, true);

            if (isset($xmlResponse['data'][$objectName . 's'][$objectName])) {
                $objects = $xmlResponse['data'][$objectName . 's'][$objectName];
            } elseif (isset($xmlResponse['data'][$objectName])) {
                $objects = $xmlResponse['data'][$objectName];
            } else {
                $objects = [];
            }

            // Если один элемент, то перемещаем кго в индексированный массив
            if (!isset($objects[0])) {
                $objects = [$objects];
            }

            // Для всего индексированного массива объектов собираем новый массив из модели
            foreach ($objects as $object) {
                $objectArray = json_decode(json_encode($object), true);
                // Индекс массива
                $primaryKey = isset($object[$objectKey]) ? (string)$object[$objectKey] : null;
                if (!$self->fields) {
                    $resultObject = $objectArray;
                } else {
                    $objectFromProperties = [];
                    foreach ($self->fields as $property) {
                        $objectFromProperties[$property] = isset($objectArray[$property]) ? $objectArray[$property] : null;
                    }

                    $resultObject = $objectFromProperties;
                }

                if ($primaryKey) {
                    $planfixObjects[$primaryKey] = $resultObject;
                } else {
                    $planfixObjects[] = $resultObject;
                }
            }
        }

        if ($itemKey && isset($itemKey[0], $itemKey[1])) {
            switch ($itemKey[0]) {
                case 'Custom':
                    // Map with Cabinet IDs
                    $planfixObjects = PlanfixBase::makeCustomKeys(
                        $planfixObjects,
                        $itemKey[1]
                    );
                    break;
                case 'Main':
                    $planfixObjects = PlanfixBase::makeMainKeys(
                        $planfixObjects,
                        $itemKey[1]
                    );
                    break;
            }

        }

        if ((bool)$cacheKey !== false) {
            Yii::$app->cache->set($cacheKey, $planfixObjects, $self->cacheDuration);
        }
        return $planfixObjects;
    }

    /**
     * Извлечь кастомные поля как свойства объекта Планфикс
     * @param $objects
     * @param array $keys
     * @return array
     */
    public static function catchCustomValues($objects, array $keys)
    {
        if (empty($objects)) {
            return [];
        }

        foreach ($objects as &$object) {
            if (!isset($object['customData']['customValue'])) {
                continue;
            }
            foreach ($object['customData']['customValue'] as $customValue) {
                foreach ($keys as $key => $alias) {
                    if (!isset($customValue['field']['id']) || $customValue['field']['id'] != $key) {
                        continue;
                    }
                    if (isset($customValue['text']) && is_string($customValue['text'])) {
                        $object[$alias] = $customValue['text'];
                    } elseif (isset($customValue['value']) && is_string($customValue['value'])) {
                        $object[$alias] = $customValue['value'];
                    } else {
                        $object[$alias] = null;
                    }
                }
            }
        };
        return $objects;
    }

    /**
     * Собрать массив объектов Планфикс по кастомному полю
     * @param $objects
     * @param $key
     * @return array
     */
    public static function makeCustomKeys($objects, $key)
    {
        $out = [];
        if (empty($objects)) {
            return $out;
        }

        foreach ($objects as &$object) {
            if (!isset($object['customData']['customValue'])) {
                continue;
            }
            foreach ($object['customData']['customValue'] as $customValue) {
                if ($key == $customValue['field']['id'] && $customValue['value']) {
                    $out[$customValue['value']] = $object;
                }
            }
        }
        return $out;
    }

    /**
     * Собрать массив объектов Планфикс по основному полю
     * @param $objects
     * @param $key
     * @return array
     */
    public static function makeMainKeys($objects, $key)
    {
        $out = [];
        foreach ($objects as &$object) {
            if (!isset($object[$key])) {
                continue;
            }
            $out[$object[$key]] = $object;
        }
        return $out;
    }

    /**
     * Получить всех юзеров Планфикс и взять как свойство
     * @param array $params
     * @return array
     */
    public function getAllPlanfixUsers($params = [])
    {
        // Get Planfix Contacts
        $this->contactsCacheKey = $this->objectName . '_' . json_encode($params);

        return $this->planfixUsers = PlanfixUser::find(
            $params,
            $this->objectName,
            $this->objectKey,
            $this->contactsCacheKey,
            ['Main', 'email']
        );
    }

    /**
     * Закгрузка контактов в Планфикс
     * @param $toAddSyncCollection SyncBase[]
     * @param string $type
     * @return bool|null
     * @throws SyncException
     */
    public function addContacts($toAddSyncCollection, $type = 'Company')
    {
        if (empty($toAddSyncCollection)) {
            return null;
        }
        LogHelper::action("Add " . $type);
        foreach ($toAddSyncCollection as $syncRecord) {
            $this->addContactToPlanfix($syncRecord, null, $type);
        }
        return true;
    }

    /**
     * Создает контакт в Планфиксе и устанавливает параметры
     * объекта синхронизации в зависимости от успеха операции
     * @param $syncRecord
     * @param null $object
     * @param string $type
     * @return bool
     * @throws SyncException
     */
    public function addContactToPlanfix($syncRecord, $object = null, $type = 'Company')
    {
        LogHelper::success("Add new $type:");
        TimerHelper::timerRun("add_contact");

        if (!$object) {
            $object = $syncRecord->getSyncCabinetObject();
        }

        if (!$object) {
            throw new SyncException("Not found sync object");
        }

        TimerHelper::timerRun();
        $contact = new PlanfixContact();
        TimerHelper::timerStop(null, "new Planfix Object create...");

        $mapped = $contact->toPlanfix($object, $syncRecord->type . $type . 'Map');
        // Если не удается добавить контакт
        $addedContact = $contact->add();
        if (!$addedContact) {
            if ($contact->error->code != '8007') {
                return false;
            }

            // И если ошибка - Дубирование Email - то переправляем в обновления
            TimerHelper::timerRun();
            LogHelper::warning("Поиск дубля контакта по email " . $mapped['email'] . "...");
            $cloneEmail = $mapped['email'] ?? null;
            if (!$cloneEmail) {
                $syncRecord->delete();
                return false;
            }

            $planfixClone = current(PlanfixContact::findByEmail($cloneEmail));
            if (!$planfixClone) {
                LogHelper::warning("Not found $cloneEmail in Planfix. Delete sync record");
                $syncRecord->delete();
                return false;
            }

            $syncRecord->planfix_id = $planfixClone['id'] ?? null;
            $syncRecord->planfix_general_id = $planfixClone['general'] ?? null;
            if (!($syncRecord->planfix_id && $syncRecord->planfix_general_id)) {
                TimerHelper::timerStop(null, "clone resolve");
                return false;
            }

            $syncRecord->status_sync = SyncBase::STATUS_UPDATE;
            try {
                $syncRecord->save();
            } catch (\Exception $exception) {
                $syncRecord->delete();
            }

            TimerHelper::timerStop(null, "clone resolve");
            return false;
        }

        TimerHelper::timerRun();
        $data = $addedContact['data'][$contact->objectName];

        $syncRecord->planfix_id = $data['id'] ?? null;
        $syncRecord->planfix_userid = $data['userid'] ?? null;
        $syncRecord->planfix_general_id = $data['general'] ?? null;
        $syncRecord->status_sync = SyncBase::STATUS_NONE;
        $syncRecord->save();
        TimerHelper::timerStop(null, "Update sync row ", "DATABASE");

        // Если пользователь - обновляем контрагентов
        if ($type === 'User') {
            $this->updateUserContactors($syncRecord, $contact);
        }

        // Обновление доступа
        $this->updateAccess($syncRecord, $object, $type);
        TimerHelper::timerStop("add_contact", "Добавление нового контакта - итог");

        return true;
    }

    /**
     * Сборка в объект Planfix по маппингу "{$mapName}"
     * @param $object
     * @param $map
     * @return array|null
     */
    public function toPlanfix($object, $map)
    {
        TimerHelper::timerRun();
        if (is_string($map)) {
            if (!isset($this->{$map})) {
                TimerHelper::timerStop(null, "Map to Planfix");
                return null;
            }
            $map = $this->{$map};
        }
        $outArray = [];


        foreach ($map as $mapTo => $mapFrom) {
            $outArray[$mapTo] = $this->calculateMapField($mapFrom, $mapTo, $object);
        }

        if (isset($this->id)) {
            $outArray['id'] = $this->id;
        }
        TimerHelper::timerStop(null, "Map to Planfix");
        return $outArray;
    }

    /**
     * Получнеие зачения поля по логике маппинга
     * 1. Значение, если не массив
     * 2. Если массив, то конактенация из
     *      а. $объект->поле,
     *      б. $this->метод($объект), если значение массива начинается с ::
     * @param $mapFrom
     * @param $mapTo
     * @param $object
     * @return string
     */
    public function calculateMapField($mapFrom, $mapTo, $object)
    {
        if (!is_array($mapFrom)) {
            return $this->{$mapTo} = $outArray[$mapTo] = $mapFrom;
        }
        $value = '';

        foreach ($mapFrom as $mapFromKey => $mapFromItem) {
            if (is_string($mapFromKey) && substr($mapFromKey, 0, 2) == '::') {
                $method = str_replace('::', '', $mapFromKey);

                if (method_exists($this, $method)) {
                    $parameters = [
                        'object' => $object,
                        'params' => $mapFromItem,
                    ];
                    $calcResult = call_user_func_array([$this, $method], $parameters);

                    if (is_array($calcResult)) {
                        $value = $calcResult;
                    } else {
                        $value .= $calcResult;
                    }
                }
            } elseif (is_numeric($mapFromKey)) {
                if (substr($mapFromItem, 0, 1) == '$') {
                    $limit = 1;
                    $mapFromItem = str_replace('$', '', $mapFromItem, $limit);
                    $incValue = isset($object->{$mapFromItem}) ? $object->{$mapFromItem} : null;
                } else {
                    $incValue = $mapFromItem;
                }

                if (is_string($value) && is_string($mapFromItem)) {
                    $value .= $incValue;
                }
                if (is_array($value)) {
                    $value[] = $incValue;
                }
            } else {
                continue;
            }
        }

        return $this->{$mapTo} = $value;
    }

    /**
     * Добавление объекта Planfix
     * @return bool|array
     */
    public function add()
    {
        TimerHelper::timerRun();
        $request = [
            $this->objectName => $this->prepareObject()
        ];
        $added = $this->planfixApi->api(
            $this->objectName . '.add',
            $request,
            true
        );

        if (!$added['data'][$this->objectName]['id'] ?? null) {
            $this->error = PlanfixHelper::parseError($added);
            LogHelper::critical("Add new " . $this->objectName . ", " . $this->id . " failed!");
            LogHelper::critical("Reason: " . ($this->error->description ?? ''));
            LogHelper::error(json_encode($request, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            LogHelper::error(json_encode($added, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            TimerHelper::timerStop(null, "Planfix $this->objectName ADD failed...", "PF");
            return false;
        }

        TimerHelper::timerStop(null, "Planfix $this->objectName ADD", "PF");
        return $added;

    }

    /**
     * Сборка объекта по маске $fields
     * @param $object
     * @return array
     */
    public function prepareObject($object = [])
    {
        if (!isset($this->fields)) {
            return $object;
        }
        $planfixObject = [];
        foreach ($this->fields as $field) {
            if (isset($this->{$field}) && $this->{$field} !== false) {
                $planfixObject[$field] = $this->{$field};
            }
        }
        return $planfixObject;
    }

    /**
     * Добавление контрагентов к контакту Планфикс
     * @param $syncRecord SyncBase
     * @param $planfixContact PlanfixContact
     */
    public function updateUserContactors($syncRecord, $planfixContact)
    {
        TimerHelper::timerRun();
        $contractors = [];

        foreach (['leads', 'trade'] as $prefix) {
            if ($syncRecord->{$prefix . '_cid'}) {
                /**
                 * @var $contractorObject SyncPlanfixCompanies
                 */
                $contractorObject = SyncPlanfixCompanies::find()
                    ->where(['=', $prefix . '_id', $syncRecord->{$prefix . '_cid'}])
                    ->andWhere(['=', 'type', $syncRecord->type])
                    ->one();

                if (!$contractorObject) {
                    LogHelper::warning("Не найдены контрагенты...", 1);
                    continue;
                }
                $contractors[] = $contractorObject->planfix_id;
            }
        }

        TimerHelper::timerStop(null, "contractors prepare");

        $planfixContact->updateContractors($syncRecord->planfix_id, $contractors);
    }

    /**
     * Установка раздела Участники для контакта Планфикс
     * @param $syncRecord SyncBase
     * @param $object
     * @param $type
     * @return bool
     */
    public function updateAccess($syncRecord, $object, $type)
    {
        TimerHelper::timerRun();
        // Находим соответствующий контакту таск
        if (!$taskId = $syncRecord->planfix_task_id) {
            if (!$taskId = $this->planfixWs->getTaskIdByContact($syncRecord->planfix_id)) {
                LogHelper::error("ID $syncRecord->planfix_id not found via Planfix Web-Service", 2);
                TimerHelper::timerStop();
                return false;
            }
        }

        if (!$syncRecord->planfix_task_id) {
            $syncRecord->planfix_task_id = $taskId;
            $syncRecord->save();
        }

        if ($type === 'Company') {
            /**
             * @var $object CabinetCompany
             * @var $employee CabinetEmployee
             */
            $employee = $object->employee;
        } else {
            /**
             * @var $object CabinetCompanyUser
             * @var $employee CabinetEmployee
             * @var $company CabinetCompany
             */
            if (!$company = $object->{$syncRecord->type}) {
                LogHelper::error("$syncRecord->type not found for user...", 2);
                return false;
            }
            $company->leads_id = $object->leads_id ? $company->id : null;
            $company->trade_id = $object->trade_id ? $company->id : null;
            $employee = $company->employee;
        }

        if (!$employee) {
            LogHelper::error("Employee not found...", 2);
            $employeePlanfixId = null;
        } else {
            // Поиск employee  в Планфикс
            if (!$employeePlanfix = isset($this->planfixUsers[$employee->email]) ? $this->planfixUsers[$employee->email] : null) {
                LogHelper::error("Employee not found in Planfix users-list...", 2);
                $employeePlanfixId = null;
            } else {
                $employeePlanfixId = $employeePlanfix['id'];
            }
        }

        /**
         * workers      Исполнители (ответственные)
         */
        $this->planfixWs->changeWorkers($taskId, [$employeePlanfixId]);

        /**
         * auditors     Аудиторы (могут редактировать)
         */
        $this->planfixWs->changeAuditors($taskId, [$employeePlanfixId]);

        /**
         * members      Участники (могут видеть)
         */
        $this->planfixWs->changeMembers($taskId, [$employeePlanfixId]);

        TimerHelper::timerStop(null, "Update Access for PID: $syncRecord->planfix_id", "PF");
        return true;
    }

    /**
     * Обновление контаков Планфикс
     * @param $toUpdateSyncCollection SyncBase[]
     * @param string $type
     * @return null
     * @throws SyncException
     */
    public function updateContacts($toUpdateSyncCollection, $type = 'Company')
    {
        if (empty($toUpdateSyncCollection)) {
            return null;
        }
        LogHelper::action("Update " . $type);
        foreach ($toUpdateSyncCollection as $syncRecord) {
            //Timer
            LogHelper::success("Update $syncRecord->planfix_id:");
            TimerHelper::timerRun("upd_contact");

            if(!$object = $syncRecord->getSyncCabinetObject()){
                throw new SyncException("Not found sync object " . sprintf('%s_%s', $syncRecord->leads_id, $syncRecord->trade_id));
            }

            TimerHelper::timerRun();

            $planfixContact = new PlanfixContact();
            $planfixContact->id = $syncRecord->planfix_id;
            unset($planfixContact->contractors);

            TimerHelper::timerStop(null, "new Planfix Object create...");

            $planfixContact->toPlanfix($object, $syncRecord->type . $type . 'Map');
            if ($updated = $planfixContact->update()) {
                TimerHelper::timerRun();
                $data = $updated['data'][$planfixContact->objectName];
                $syncRecord->planfix_id = $data['id'] ?? null;
                $syncRecord->planfix_general_id = $data['general'] ?? null;
                $syncRecord->status_sync = SyncBase::STATUS_NONE;
                $syncRecord->save();
                TimerHelper::timerStop(null, "Update sync row ", "DB");
            }

            if ($type === 'User') {
                $this->updateUserContactors($syncRecord, $planfixContact);
            }

            $this->updateAccess($syncRecord, $object, $type);
            TimerHelper::timerStop("upd_contact", "Обновление контакта - итог");
        }
        return true;
    }

    /**
     * Обновление объекта Planfix
     * @return array|bool
     */
    public function update()
    {
        TimerHelper::timerRun();

        if (!isset($this->id)) {
            TimerHelper::timerStop(null, "Planfix $this->objectName Update", "PF");
            return [];
        }
        $updated = $this->planfixApi->api(
            $this->objectName . '.update',
            [
                'id' => $this->id,
                $this->objectName => $this->prepareObject()
            ],
            true
        );

        if (!$updated['data'][$this->objectName]['id'] ?? null) {
            $this->error = PlanfixHelper::parseError($updated);
            LogHelper::critical("Update of " . $this->objectName . ", " . $this->id . " failed!");
            LogHelper::critical("Reason: " . ($this->error->description ?? ''));
            LogHelper::error(json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            TimerHelper::timerStop(null, "Planfix object Update", "PF");
            return false;
        }

        TimerHelper::timerStop(null, "Planfix $this->objectName Update", "PF");
        return $updated;
    }

    /**
     * Загрузка заметок Планфикс
     * @param $toAddSyncCollection SyncPlanfixNotes[]
     * @return bool|null
     * @throws SyncException
     */
    public function addNotes($toAddSyncCollection)
    {
        if (empty($toAddSyncCollection)) {
            return false;
        }
        LogHelper::action("Add Notes");
        foreach ($toAddSyncCollection as $syncRecord) {
            LogHelper::success("Add new note:");
            TimerHelper::timerRun("add_note");

            if(!$noteObject = $syncRecord->getSyncCabinetObject()){
                throw new SyncException("Not found sync object");
            }

            /**
             * @var $noteObject CabinetAccountNote
             */
            $planfixNote = new PlanfixActionContact();
            $planfixNote->toPlanfix($noteObject, 'actionContactMap');

            if ($addedNote = $planfixNote->add()) {
                TimerHelper::timerRun();
                $syncRecord->planfix_id = $addedNote['data'][$planfixNote->objectName]['id'];
                $syncRecord->status_sync = SyncBase::STATUS_NONE;
                $syncRecord->save();
                TimerHelper::timerStop(null, "Update sync row ", "DB");
            }

            TimerHelper::timerStop("add_note", "Добавление новой заметки - итог");

        }
        return true;
    }

    /**
     * Обновление заметок Планфикс
     * @param $toUpdateSyncCollection SyncPlanfixNotes[]
     * @return bool|null
     * @throws SyncException
     */
    public function updateNotes($toUpdateSyncCollection)
    {
        if (empty($toUpdateSyncCollection)) {
            return false;
        }
        LogHelper::action("Update Notes");
        foreach ($toUpdateSyncCollection as $syncRecord) {
            LogHelper::success("Update note:");
            TimerHelper::timerRun("update_note");

            if(!$noteObject = $syncRecord->getSyncCabinetObject()){
                throw new SyncException("Not found sync object");
            }
            /**
             * @var $noteObject CabinetAccountNote
             */
            $planfixNote = new PlanfixActionContact();
            $planfixNote->toPlanfix($noteObject, 'actionContactMap');
            $planfixNote->id = $syncRecord->planfix_id;

            if ($updated = $planfixNote->update()) {
                TimerHelper::timerRun();
                $syncRecord->planfix_id = (int)$updated['data'][$planfixNote->objectName]['id'];
                $syncRecord->status_sync = SyncBase::STATUS_NONE;

                $syncRecord->save();
                TimerHelper::timerStop(null, "Update sync row ", "DB");
            }
            TimerHelper::timerStop("update_note", "Обновление заметки - итог");
        }
        return true;
    }

    /**
     * Загрузка чатов Планфикс
     * @param $toAddSyncCollection SyncPlanfixChats[]
     * @return bool
     * @throws SyncException
     */
    public function addChats($toAddSyncCollection)
    {
        if (empty($toAddSyncCollection)) {
            return false;
        }
        LogHelper::action("Add Chats");
        foreach ($toAddSyncCollection as $syncRecord) {
            LogHelper::success("Add new chat:");
            TimerHelper::timerRun("add_chat");
            /**
             * @var $chatObject CabinetChat
             */
            if(!$chatObject = $syncRecord->getSyncCabinetObject()){
                throw new SyncException("Not found sync object");
            }

            $planfixChat = new PlanfixTask();

            // Если у чата нет клиента...
            if (!$planfixChat->getChatClient($chatObject, '')) {
                TimerHelper::timerRun();

                $planfixNewPhantom = new PlanfixContact();
                // Если есть email в параметрах чата, то добавляем новый фантом
                if ($email = $chatObject->extractEmail()) {
                    $planfixNewPhantom->email = $chatObject->extractEmail();
                    $planfixNewPhantom->name = 'Гость (' . $email . ')';
                    $planfixNewPhantom->isCompany = false;
                    $planfixNewPhantom->canBeClient = true;
                    $planfixNewPhantom->canBeWorker = true;
                    $planfixNewPhantom->group = null;
                    $planfixNewPhantom->havePlanfixAccess = false;

                    if ($addedPhantom = $planfixNewPhantom->add()) {
                        print "Created new phantom with email " . $email . "\n";
                        $phantomCreated = true;
                        $data = $addedPhantom['data'][$planfixNewPhantom->objectName];
                        if ($planfixNewPhantom->email || $planfixNewPhantom->name) {
                            $syncPhantomRecord = new SyncPlanfixUnknownUsers();
                            $syncPhantomRecord->planfix_id = $data['id'];
                            $syncPhantomRecord->planfix_userid = $data['userid'] ?? null;
                            $syncPhantomRecord->planfix_general_id = $data['general'] ?? null;
                            $syncPhantomRecord->email = $planfixNewPhantom->email;
                            $syncPhantomRecord->name = $planfixNewPhantom->name;
                            $syncPhantomRecord->save();
                        }
                    }
                }
                TimerHelper::timerStop(null, "phantom resolve");
            }

            $planfixChat->toPlanfix($chatObject, $planfixChat->taskChatMap);

            if ($addedChat = $planfixChat->add()) {
                TimerHelper::timerRun();
                $data = $addedChat['data'][$planfixChat->objectName];
                $syncRecord->planfix_id = $data['id'] ?? null;
                $syncRecord->planfix_general_id = $data['general'] ?? null;
                $syncRecord->status_sync = SyncBase::STATUS_NONE;
                $this->addChatMessages($chatObject, $planfixChat, $syncRecord->planfix_id);
                $syncRecord->save();
                TimerHelper::timerStop(null, "Update sync row ", "DB");
            }

            TimerHelper::timerStop("add_chat", "Добавление нового чата - итог");

        }
        return true;
    }

    /**
     * Обновление чатов Планфикс
     * @param $toUpdateSyncCollection SyncPlanfixChats[]
     * @return bool
     * @throws SyncException
     */
    public function updateChats($toUpdateSyncCollection)
    {
        if (empty($toUpdateSyncCollection)) {
            return false;
        }
        LogHelper::action("Update Chats");
        foreach ($toUpdateSyncCollection as $syncRecord) {
            LogHelper::success("Update chat:");
            TimerHelper::timerRun("update_chat");

            /**
             * @var $chatObject CabinetChat
             */
            if(!$chatObject = $syncRecord->getSyncCabinetObject()){
                throw new SyncException("Not found sync object");
            }

            $planfixChat = new PlanfixTask();

            $planfixChat->toPlanfix($chatObject, $planfixChat->taskChatMap);
            $planfixChat->id = $syncRecord->planfix_id;

            if ($updated = $planfixChat->update()) {
                TimerHelper::timerRun();
                $syncRecord->status_sync = SyncBase::STATUS_NONE;
                $syncRecord->save();
                TimerHelper::timerStop(null, "Update sync row ", "DB");
            }
            TimerHelper::timerStop("update_chat", "Обновление чата - итог");
        }
        return true;
    }

    /**
     * Загркузка сообщений Чата в Планфикс
     * @param $chat CabinetChat
     * @param $planfixChat PlanfixTask
     * @param $planfixChatId
     * @return bool
     */
    public function addChatMessages($chat, $planfixChat, $planfixChatId)
    {
        LogHelper::success("Add chat`s messages");
        TimerHelper::timerRun("add_chat_messages");
        /**
         * @var $messages CabinetChatMessage[]
         */
        if (!$messages = $chat->messages) {
            return false;
        }
        foreach ($messages as $message) {
            $message->planfixTaskId = $planfixChatId;

            TimerHelper::timerRun();
            $message->planfixChatWorkers['agent'] = $planfixChat->getMembers($chat, '');
            TimerHelper::timerStop(null, "Get message members");

            TimerHelper::timerRun();
            $message->planfixChatWorkers['client'] = $planfixChat->getOwner($chat, '');
            TimerHelper::timerStop(null, "Get message owners");

            $planfixChatMessage = new PlanfixActionTask();
            $planfixChatMessage->toPlanfix($message, 'actionChatMap');
            $planfixChatMessage->add();
        }
        TimerHelper::timerStop("add_chat_messages", "Добавление сообщений чата");
        return true;
    }

    /**
     * Загрузка новых Запросов на выплату
     * @param $toPushSyncCollection SyncPlanfixPayments[]
     * @return bool
     */
    public function sendPaymentCabinetToPlanfix($toPushSyncCollection)
    {

        if (empty($toPushSyncCollection)) {
            return false;
        }

        foreach ($toPushSyncCollection as $syncRecord) {
            $payoutObject = $syncRecord->getSyncCabinetObject();
            $planfixPayoutTask = new PlanfixPayoutTask();
            $planfixPayoutTask->toPlanfix($payoutObject, 'taskPayoutMap');

            if ($added = $planfixPayoutTask->add()) {
                $data = $added['data'][$planfixPayoutTask->objectName];
                $syncRecord->planfix_id = $data['id'] ?? null;
                $syncRecord->planfix_general_id = $data['general'] ?? null;
                $syncRecord->status_sync = SyncBase::STATUS_NONE;
                $syncRecord->status_payment = SyncPlanfixPayments::STATUS_PAYMENT_NEED_APPROVE;
                $syncRecord->save();
            }
        }
        return true;
    }

    /**
     * @param $toAddSyncCollection SyncPlanfixOffers[]
     * @return bool
     * @throws SyncException
     */
    public function addOffers($toAddSyncCollection)
    {
        if (empty($toAddSyncCollection)) {
            return false;
        }
        LogHelper::action("Add Offers");
        foreach ($toAddSyncCollection as $syncRecord) {
            LogHelper::success("Add new offer:");
            TimerHelper::timerRun("add_offer");
            /**
             * @var $offerObject CabinetOffer
             */
            if(!$offerObject = $syncRecord->getSyncCabinetObject()){
                throw new SyncException("Not found sync object");
            }

            $planfixProject = new PlanfixProjectOffer();
            $planfixProject->toPlanfix($offerObject, $planfixProject->projectOfferMap);
            if ($added = $planfixProject->add()) {
                TimerHelper::timerRun();
                $data = $added['data'][$planfixProject->objectName];
                $syncRecord->planfix_id = $syncRecord->planfix_general_id = $data['id'] ?? null;
                $syncRecord->status_sync = SyncBase::STATUS_NONE;
                $syncRecord->save();
                TimerHelper::timerStop(null, "Update sync row ", "DB");
            }
            TimerHelper::timerStop("add_offer", "Добавление нового оффера - итог");
        }
        return true;
    }

    /**
     * Обновление чатов Планфикс
     * @param $toUpdateSyncCollection SyncPlanfixOffers[]
     * @return bool
     * @throws SyncException
     */
    public function updateOffers($toUpdateSyncCollection)
    {
        if (empty($toUpdateSyncCollection)) {
            return false;
        }
        LogHelper::action("Update Offers");
        foreach ($toUpdateSyncCollection as $syncRecord) {
            LogHelper::success("Update offer:");
            TimerHelper::timerRun("update_offer");

            /**
             * @var $offerObject CabinetOffer
             */
            if(!$offerObject = $syncRecord->getSyncCabinetObject()){
                throw new SyncException("Not found sync object");
            }

            $planfixProject = new PlanfixProjectOffer();
            $mapped = $planfixProject->toPlanfix($offerObject, $planfixProject->projectOfferMap);
            $planfixProject->id = $syncRecord->planfix_id;

            if ($updated = $planfixProject->update()) {
                TimerHelper::timerRun();
                $data = $updated['data'][$planfixProject->objectName];
                $syncRecord->status_sync = SyncBase::STATUS_NONE;
                $syncRecord->planfix_general_id = $syncRecord->planfix_id = $data['id'];
                $syncRecord->save();
                TimerHelper::timerStop(null, "Update sync row ", "DB");
            }
            TimerHelper::timerStop("update_offer", "Обновление оффера - итог");
        }
        return true;
    }
}
