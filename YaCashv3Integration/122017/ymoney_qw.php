<?php
require_once __DIR__ . '/ymoney_base.php';

/**
 * Подтип платежной системы Яндекс.Касса,
 * который использует платежы через QIWI Wallet
 */
class custom_paysystem_ymoney_qw extends custom_paysystem_ymoney_base {

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject(self::PAYSYSTEM_ID_PREFIX . 'qw');
	}

	/**
	 * @inheritdoc
	 */
	public function enabled() {
		if ($this->choosePaysystemOnYandex) {
			return false;
		}

		return parent::enabled();
	}

	/**
	 * @inheritdoc
	 */
	protected function getPaymentTypeCode() {
		return 'qiwi';
	}

	/**
	 * @inheritdoc
	 */
	protected function getPaymentCustomParams(order $order) {
		return array(
			'phone' => (string) $order->getObject()->getPropByName('cust_phone')->getValue()
		);
	}
}