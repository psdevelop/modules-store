<?php

use \UmiCms\Modules\Emarket\PaySystem\Sberbank;

/**
 * Платежная система Сбербанк-эквайринг
 * Class custom_paysystem_sberbank
 */
class custom_paysystem_sberbank extends custom_paysystem_base {
	/** @const кодовое имя платежной системы */
	const NAME = 'sberbank';

	/** @var Sberbank\Integration API взаимодействия с эквайрингом */
	protected $integration;
	/** @var string адрес оплаты для клиента на стороне эквайринга */
	protected $payUrl;

	/** @var Sberbank\Settings настройки интеграции */
	protected $settings;

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject(self::NAME);
		$this->settings = new Sberbank\Settings( regedit::getInstance() );

		$this->integration = new Sberbank\Integration( $this->settings->getLogin(), $this->settings->getPassword(), new Sberbank\RequestSender() );
		$this->integration->setTestMode( $this->settings->isTestMode() );
	}

	/**
	 * Устаналивает объект для взаимодействия с API эквайринга от сбербанка
	 * @param Sberbank\Integration $integration
	 */
	public function setIntegration(Sberbank\Integration $integration) {
		$this->integration = $integration;
	}

	/**
	 * Устанавливает объект настроек для эквайринга
	 * @param Sberbank\Settings $settings
	 */
	public function setSettings(Sberbank\Settings $settings) {
		$this->settings = $settings;
	}

	/** @inheritdoc */
	public function enabled() {
		return ( $this->settings->getActive() && $this->settings->getLogin() && $this->settings->getPassword() );
	}

	/** @inheritdoc */
	public function callback() {
		$buffer = outputBuffer::current();

		try {
			$externalOrderId = getRequest('orderId');
			$orderInfo = $this->integration->getOrderStatus($externalOrderId);
			$sberbankOrder = new Sberbank\Order($orderInfo);

			$order = $this->getOrder( $sberbankOrder->getShopNumber() );

			$logger = new Sberbank\Logger($order);
			$logger->addLog($sberbankOrder);

			if ( !$sberbankOrder->isPaid() ) {
				throw new ErrorException();
			}

			$order->setOrderStatus('accepted');
			$order->setPaymentStatus('accepted');
			$order->commit();

			$buffer->redirect( order::getSuccessUrl() );
		} catch (Exception $e) {
			$buffer->redirect( order::getFailUrl() );
		}
	}

	/**
	 * Возвращает @see custom_paysystem_sberbank::$payUrl
	 * @return string
	 */
	public function getPayUrl() {
		return $this->payUrl;
	}

	/**
	 * Возвращает объект заказа по переданному идентификатору
	 * На данный момент нет подходящего места для этого метода
	 * @param string $orderId идентификатор заказа
	 * @return order
	 * @throws ErrorException если заказ не найден
	 */
	protected function getOrder($orderId) {
		$order = order::get($orderId);

		if ($order instanceof order) {
			return $order;
		}

		throw new ErrorException( sprintf('Order #%s not found', $orderId) );
	}

	/** @inheritdoc */
	public function onChoose(order $order) {
		$info = $this->integration->registerOrder( $order, $this->settings->getGatewayUrl() );

		if ($info['errorCode']) {
			throw new ErrorException( $info['errorMessage'], $info['errorCode'] );
		}

		$order->setOrderStatus('payment');

		$this->payUrl = $info['formUrl'];
	}

	/** @inheritdoc */
	public function getPaymentUrl($orderId) {
		return $this->payUrl;
	}

	/** @inheritdoc */
	public function getRequestType() {
		return "GET";
	}

}