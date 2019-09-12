<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 14.09.17
 * Time: 16:40
 */

namespace app\models\links;


use app\exceptions\LinkException;

class LinkModel extends LinkBase
{
    protected static $entityNamespace;

    protected $availableTargets = [
        self::TARGET_CABINET,
        self::TARGET_PLANFIX
    ];

    public function rules()
    {
        return array_merge([
            ['target', 'in', 'range' => $this->availableTargets, 'message' => 'Недопустимое значение "{attribute} ({})"'],
        ], parent::rules());
    }

    /**
     * LinkContact constructor.
     * @param array $config
     * @return LinkBase
     * @throws \Exception
     */
    public static function make(array $config = [])
    {
        if (!$config['target']) {
            LinkException::validationError("Недопустимая цель!");
        }
        $validateModel = new self();
        $validateModel->setAttributes($config);
        $validateModel->validate();
        if ($validateModel->hasErrors()) {
            LinkException::validationError(null, $validateModel->errors, $validateModel->helpView ?? null);
        }
        /**
         * @var $model LinkBase
         */
        $modelClass = __NAMESPACE__ . "\\" . static::$entityNamespace . "\\" . ucfirst($config['target']);
        try {
            $model = new $modelClass();
            $model->setAttributes($config);
            return $model;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}