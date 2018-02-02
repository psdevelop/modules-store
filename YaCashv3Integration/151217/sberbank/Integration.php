<?php

namespace UmiCms\Modules\Emarket\PaySystem\Sberbank;

/**
 * Класс взаимодействия с API эквайринга от Сбербанка
 * @link https://developer.sberbank.ru/acquiring
 * Class Integration
 * @package UmiCms\Modules\Emarket\PaySystem\Sberbank
 */
class Integration {
	/** @const URL для отправки запросов тестового режима */
	const TEST_URL = 'https://3dsec.sberbank.ru/payment/rest/';
	/** @const URL для отправки запросов боевого режима */
	const PROD_URL = 'https://securepayments.sberbank.ru/payment/rest/';

	/** @var string логин для доступа к API */
	private $login;
	/** @var string пароль для доступа к API */
	private $password;
	/** @var string включен ли режим тестирования API */
	private $isTestMode;
	/** @var string включена ли двух-стадийная оплата */
	private $twoStage;

	/** @var RequestSender отправитель запросов */
	private $requestSender;

	/**
	 * Integration constructor.
	 * @param string $login логин для доступа к API
	 * @param string $password пароль для доступа к API
	 * @param RequestSender $requestSender отправитель запросов
	 */
	public function __construct($login, $password, RequestSender $requestSender) {
		$this->login = $login;
		$this->password = $password;
		$this->requestSender = $requestSender;
	}

	/**
	 * Устанавливает режим тестирования API
	 * @param bool $mode если true - включает режим тестирования, если false - выключает
	 */
	public function setTestMode($mode) {
		$this->isTestMode = $mode;
	}

	/**
	 * Устанавливает режим двух-стадийной оплаты
	 * @param bool $twoStage, если true - включает режим, если false - выключает
	 */
	public function setTwoStage($twoStage) {
		$this->twoStage = $twoStage;
	}

	/**
	 * Возвращает ответ от API с данными о статусе заказа и оплаты
	 * @param string $orderId идентификатор заказа на стороне эквайринга
	 * @return array
	 */
	public function getOrderStatus($orderId) {
		$data = array('orderId' => $orderId);
		return $this->request('getOrderStatusExtended.do', $data);
	}

	/**
	 * Регистрирует заказ в эквайринге
	 * @param \order $order
	 * @param string $callbackUrl адрес, на который эквайринг выполнит запрос для подтверждения оплаты
	 * @return array
	 */
	public function registerOrder(\order $order, $callbackUrl) {
		$data = array(
			'orderNumber' => $order->getId(),
			'amount' => $this->getPriceInMinUnits( $order->getActualPrice() ),
			'returnUrl' => $callbackUrl,
			'orderBundle' => $this->getCheckInfo($order)
		);

		$method = ($this->twoStage ? 'registerPreAuth.do' : 'register.do');
		return $this->request($method, $data);
	}

	/**
	 * Возвращает данные для чека
	 * @param \order $order заказ
	 * @return string JSON данные для чека
	 */
	private function getCheckInfo(\order $order) {
		$itemList = $order->getItems();

		if ( $order->getDelivery() ) {
			$itemList[] = $order->getDelivery();
		}

		$result = array(
			'cartItems' => array(
				'items' => $this->prepareCheckItems($itemList),
			),
		);

		return json_encode($result);
	}

	/**
	 * Выполняет подготовку данных товаров для чека
	 * @param array $itemList
	 * @return array данные о товарах
	 */
	private function prepareCheckItems(array $itemList) {
		$list = array();

		$position = 1;
		/**  @var \orderItem $item */
		foreach ($itemList as $item) {
			$priceInMinUnits = $this->getPriceInMinUnits( $item->getPrice() );

			$itemInfo = array();
			$itemInfo['positionId'] = $position;
			$itemInfo['name'] = $item->getName();
			$itemInfo['itemAmount'] = $priceInMinUnits * $item->getAmount();
			$itemInfo['itemCode'] = $item->getId();

			$itemInfo['quantity'] = array(
				'measure' => $item->getMeasure() ?: 'ед.',
				'value' => $item->getAmount()
			);

			$itemInfo['itemPrice'] = $priceInMinUnits;
			$itemInfo['tax'] = array(
				'taxType' => $item->getNdsRate()->toSberbankId()
			);

			$list[] = $itemInfo;

			$position += 1;
		}

		return $list;
	}

	/**
	 * Возвращает цену в минимальных единицах валюты
	 * @param float $price
	 * @return int
	 */
	private function getPriceInMinUnits($price) {
		$mantissa = strstr ($price, '.');
		$cents = substr($mantissa, 1, 2);

		return ( intval($price) * 100 ) + intval($cents);
	}

	/**
	 * Выполняет запрос к API эквайринга
	 * @param string $method имя метода API
	 * @param array $data параметры для API [<имя_параметра> => <значение_параметра> ...]
	 * @return mixed
	 */
	protected function request($method, array $data) {
		$data = array_merge($this->getAuthParams(), $data);

		$this->requestSender->setUrl(  $this->getMethodUrl($method) );
		$this->requestSender->setRequestParams($data);

		$response = $this->requestSender->request();

		$info = json_decode($response, true);
		return $info;
	}

	/**
	 * Возвращает параметры авторизации в эквайринге
	 * @return array
	 */
	private function getAuthParams() {
		return array(
			'userName' => $this->login,
			'password' => $this->password,
		);
	}

	/**
	 * Возвращает URL-адрес запроса к методу API
	 * @param string $method имя метода
	 * @return string
	 */
	private function getMethodUrl($method) {
		return $this->getUrl() . $method;
	}

	/**
	 * Возвращает URL-адрес для отправки запросов
	 * @return string
	 */
	private function getUrl() {
		if ($this->isTestMode) {
			return self::TEST_URL;
		}

		return self::PROD_URL;
	}

}