<?php
require_once __DIR__ . '/ymoney_base.php';

/**
 * Подтип платежной системы Яндекс.Касса,
 * который использует платежы через КупиВкредит
 */
class custom_paysystem_ymoney_kv extends custom_paysystem_ymoney_base {

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject(self::PAYSYSTEM_ID_PREFIX . 'kv');
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
}