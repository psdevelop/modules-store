<?php
require_once __DIR__ . '/ymoney_base.php';

/**
 * Подтип платежной системы Яндекс.Касса,
 * который использует платежы через Альфа-Клик
 */
class custom_paysystem_ymoney_ab extends custom_paysystem_ymoney_base {

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject(self::PAYSYSTEM_ID_PREFIX . 'ab');
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
		return 'alfabank';
	}

	/**
	 * @inheritdoc
	 */
	protected function getConfirmationType() {
		return 'external';
	}

}