<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 15.08.17
 * Time: 19:11
 */

namespace app\models\links;

use app\components\CabinetAPI;
use app\components\enums\ContactTypesEnum;
use app\exceptions\LinkException;
use app\components\PlanfixWebService;
use yii\base\Model;

class LinkBase extends Model
{
    const TARGET_PLANFIX = 'planfix';
    const TARGET_CABINET = 'cabinet';

    const MODULE_API = 'api';
    const MODULE_SERVICE = 'service';

    const PLATFORM_LEADS = 'leads';
    const PLATFORM_TRADE = 'trade';

    public $module;
    public $target;
    public $handler;
    public $platform;

    public $type;

    public $planfixTaskUrlMask = "http://%s.planfix.ru/task/%d";
    public $planfixContactUrlMask = "http://%s.planfix.ru/contact/%d";
    public $cabinetContactUrlMask = "http://%s/%s/default/view/%d";

    /**
     * @var PlanfixWebService
     */
    protected $planfixWs;
    /**
     * @var CabinetAPI $this->cabinetApi
     */
    public $cabinetApi;

    protected $availableTargets = [
        self::TARGET_PLANFIX,
        self::TARGET_CABINET
    ];

    protected $availableModules = [
        self::MODULE_API,
        self::MODULE_SERVICE,
    ];

    protected $availablePlatforms = [
        self::PLATFORM_LEADS,
        self::PLATFORM_TRADE
    ];

    protected $platformSynonyms = [
        'trade' => self::PLATFORM_TRADE,
        'tradeleads' => self::PLATFORM_TRADE,
        'leads' => self::PLATFORM_LEADS,
        'leads-black' => self::PLATFORM_TRADE,
    ];

    protected $typesToCabinet = [
        ContactTypesEnum::TYPE_AFFILIATE => 'webmaster',
        ContactTypesEnum::TYPE_ADVERTISER => 'advertizer'
    ];

    public function attributeLabels()
    {
        return [
            'target' => 'Цель (' . implode(' / ', $this->availableTargets) . ')',
            'handler' => 'Обработчик',
        ];
    }

    /**
     * @var $type
     * @return mixed|null
     */
    protected function getCabinetContactType($type = null)
    {
        return $this->typesToCabinet[$type ?? $this->type] ?? null;
    }

    /**
     * @var $type
     * @return mixed|null
     */
    protected function getPlanfixContactType($type = null)
    {
        return array_search($type, $this->typesToCabinet);
    }

    public function getHelpView()
    {
        return "partials/link-base_help";
    }

    public function rules()
    {
        return [
            [['target', 'handler'], 'required', 'message' => 'Не задан необходимый параметр "{attribute}"'],
            ['target', 'in', 'range' => $this->availableTargets, 'message' => 'Недопустимое значение {attribute}'],
            ['module', 'in', 'range' => $this->availableModules, 'message' => 'Недопустимое значение {attribute}'],
        ];
    }

    public function handle()
    {
        $handlerMethod = $this->handler . "Handler";
        if (!method_exists($this, $handlerMethod)) {
            throw new LinkException("", LinkException::ERROR_HEADER_INVALID_HANDLER, [$handlerMethod => ['Обработчик отсутствует']]);
        }
        return $this->{$this->handler . "Handler"}();
    }

    public function getPlatform()
    {
        $this->platform = strtolower($this->platform);
        if (in_array($this->platform, $this->availablePlatforms)) {
            return $this->platform;
        }
        return $this->platformSynonyms[$this->platform] ?? null;
    }

    /**
     * @return string
     */
    public function getPlatformIdField()
    {
        return $this->getPlatform() . "_id";
    }

    /**
     * @param $data
     * @return array
     */
    public function setResult($data)
    {
        return [
            'status' => 'success',
            'data' => $data
        ];
    }
}
