<?php

namespace UmiCms\Modules\Emarket\PaySystem\Sberbank;

/**
 * Журналирование запросов от эквайринга
 * Class Logger
 * @package UmiCms\Modules\Emarket\PaySystem\Sberbank
 */
class Logger {

	/** @var \order заказ в интернет-магазине */
	private $order;

	/**
	 * Logger constructor.
	 * @param \order $order заказ в интернет-магазине
	 */
	public function __construct(\order $order) {
		$this->order = $order;
	}

	/**
	 * Добавляет запись в журнал
	 * @param Order $sberbankOrder заказ на стороне эквайринга
	 */
	public function addLog(Order $sberbankOrder) {
		$this->order->addServiceMessage( $this->getMessage($sberbankOrder) );
	}

	/**
	 * Возвращает сообщение для записи в журнал
	 * @param Order $order заказ на стороне эквайринга
	 * @return string
	 */
	protected function getMessage(Order $order) {
		$templateFile = __DIR__ . '/logTemplate.phtml';

		if ( !file_exists($templateFile) ) {
			return '';
		}

		$info = array(
			'time' => date(DATE_ISO8601),
			'path' => getRequest('path')
		);

		ob_start();

		/** @noinspection PhpIncludeInspection */
		require $templateFile;

		return ob_get_clean();
	}

}