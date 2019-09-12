<?php

namespace app\services;

/**
 * Class Environment
 * Абстрактный класс сервиса окружения, осуществляющего
 * DI раскладку классов модуля в контейнер приложения
 * @package app\services
 */
abstract class Environment
{
    /** @const string */
    const PROJECT_LEADS = 'leads';

    /** @const string */
    const PROJECT_BLACK = 'black';

    /** @var string */
    protected $project;

    /**
     * Инициирует контейнер приложения требуемыми
     * для проекта классами модуля
     */
    public abstract function init();

    public function __construct()
    {
        $this->setEnvironment(self::PROJECT_LEADS);
    }

    public function setProject(string $project)
    {
        $this->project = $project;
        $this->init();
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function setEnvironment(string $type)
    {
        $this->setProject($type);
    }

    public function getProjectEnvironment(): string
    {
        return $this->project;
    }
}