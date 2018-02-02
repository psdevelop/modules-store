<?php

namespace UmiCms\Modules\Emarket\PaySystem\Sberbank;

/**
 * Заказ на стороне эквайринга от Сбербанка
 * Class Order
 * @package UmiCms\Modules\Emarket\PaySystem\Sberbank
 */
class Order {
	/** @const код успешной оплаты заказа */
	const SUCCESS_CODE = 0;

	/** @var array данные заказа */
	private $info;
	/** @var array идентификаторы статусов заказа, при которых он считается оплаченным */
	private $paidStatuses = array(1, 2);

	/**
	 * Order constructor.
	 * @param array $info данные заказа
	 */
	public function __construct(array $info) {
		$this->info = $info;
	}

	/**
	 * Возвращает номер связанного заказа в интернет-магазине
	 * @return string
	 */
	public function getShopNumber() {
		return $this->info['orderNumber'];
	}

	/**
	 * Возвращает описание ошибки, которая возникла при оплате заказа
	 * @return string
	 */
	public function getErrorDescription() {
		return $this->info['actionCodeDescription'];
	}

	/**
	 * Возвращает исходные данные заказа в виде строки
	 * @return string
	 */
	public function getRawInfo() {
		return var_export($this->info, true);
	}

	/**
	 * Возвращает оплачен ли заказ
	 * @return bool
	 */
	public function isPaid() {
		$success = ( $this->info['ErrorCode'] == self::SUCCESS_CODE );
		$isPaidStatus = $this->isPaidStatus( $this->info['orderStatus'] );

		return ($success && $isPaidStatus);
	}

	/**
	 * Проверяет является ли переданный статус заказа статусом, при котором заказ считается оплаченным
	 * @param int|string $status проверяемый статус заказа
	 * @return bool true - если переданный статус является статусом, при котором заказ считается оплаченным, false - в ином случае
	 */
	private function isPaidStatus($status) {
		return in_array( intval($status), $this->paidStatuses);
	}

}