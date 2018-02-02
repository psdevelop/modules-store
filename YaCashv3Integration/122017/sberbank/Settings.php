<?php

namespace UmiCms\Modules\Emarket\PaySystem\Sberbank;

/**
 * Управление настройками интеграции с эквайрингом Сбербанка
 * Class Settings
 * @package UmiCms\Modules\Emarket\PaySystem\Sberbank
 */
class Settings {

	/** @const ключ активности способа оплаты */
	const ACTIVE = '//emarket/payment/sberbank/active';
	/** @const ключ для логина в Сбербанке */
	const LOGIN = '//emarket/payment/sberbank/login';
	/** @const ключ для пароль в Сбербанке */
	const PASSWORD = '//emarket/payment/sberbank/password';
	/** @const ключ для статуса тестового режима */
	const TEST_MODE = '//emarket/payment/sberbank/test-mode';
	/** @const ключ адреса обработки ответа от платежной системы */
	const GATEWAY_URL = '//emarket/payment/sberbank/gateway';

	/** @var \iRegedit реестр сайта */
	private $registry;

	/**
	 * Settings constructor.
	 * @param \iRegedit $registry реестр сайта
	 */
	public function __construct(\iRegedit $registry) {
		$this->registry = $registry;
	}

	/**
	 * Устанавливает активность способа оплаты
	 * @param bool $active если true, то активен, если false, то не активен
	 */
	public function setActive($active) {
		$this->registry->setVal( self::ACTIVE, $this->valueForBool($active) );
	}

	/**
	 * Возвращает активность способа оплаты
	 * @return bool
	 */
	public function getActive() {
		return ( (string) $this->registry->getVal(self::ACTIVE) === "1" );
	}

	/**
	 * Устанавливает логин для доступа к API эквайринга
	 * @param string $login логин
	 */
	public function setLogin($login) {
		$this->registry->setVal(self::LOGIN, $login);
	}

	/**
	 * Возвращает логин для доступа к API эквайринга
	 * @return string
	 */
	public function getLogin() {
		return (string) $this->registry->getVal(self::LOGIN);
	}

	/**
	 * Устанавливает пароль для доступа к API эквайринга
	 * @param string $password пароль
	 */
	public function setPassword($password) {
		$this->registry->setVal(self::PASSWORD, $password);
	}

	/**
	 * Возвращает пароль для доступа к API эквайринга
	 * @return string
	 */
	public function getPassword() {
		return (string) $this->registry->getVal(self::PASSWORD);
	}

	/**
	 * Возвращает включен ли тестовый режим
	 * @return bool true - если включен, false - если выключен
	 */
	public function isTestMode() {
		return ( (string) $this->registry->getVal(self::TEST_MODE) === '1' );
	}

	/**
	 * Устанавливает тестовый режим
	 * @param bool $enabled true для включения тестового режима и false для выключения
	 */
	public function setTestMode($enabled) {
		$this->registry->setVal( self::TEST_MODE, $this->valueForBool($enabled) );
	}

	/**
	 * Возвращает значение для хранения по значению булева типа
	 * @param $bool
	 * @return string
	 */
	private function valueForBool($bool) {
		return ($bool ? '1' : '0');
	}

	/**
	 * Устанавливает адрес обработки ответа от платежной системы
	 * @param string $url URL-адрес
	 */
	public function setGatewayUrl($url) {
		$this->registry->setVal(self::GATEWAY_URL, $url);
	}

	/**
	 * Возвращает адрес обработки ответа от платежной системы
	 * @return string
	 */
	public function getGatewayUrl() {
		return (string) $this->registry->getVal(self::GATEWAY_URL);
	}

	/**
	 * Возвращает доступен ли способ оплаты на сервере
	 * @return bool true - если доступен, false - в ином случае
	 */
	public function isAvailable() {
		return ( \umihost_config::get('paysystem', 'sberbank') === "1" );
	}
}