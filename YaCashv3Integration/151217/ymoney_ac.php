<?php
require_once __DIR__ . '/ymoney_base.php';

/**
 * Подтип платежной системы Яндекс.Касса,
 * который использует платежы с банковских карт
 */
class custom_paysystem_ymoney_ac extends custom_paysystem_ymoney_base {

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject(self::PAYSYSTEM_ID_PREFIX . 'ac');
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
		return 'bank_card';
	}

}