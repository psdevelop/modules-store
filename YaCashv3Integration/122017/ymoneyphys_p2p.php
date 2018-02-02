<?php
require_once __DIR__ . '/ymoneyphys_base.php';

/**
 * Класс платежной системы Яднекс.Деньги для физических лиц.
 *
 * Оплата со счета в Яндекс.Деньгах.
 */
class custom_paysystem_ymoneyphys_p2p extends custom_paysystem_ymoneyphys_base {
	/**
	 * {@inheritdoc}
	 */
	protected function getPaysystemType() {
		return 'PC';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getPaysystemGuide() {
		return 'ymoneyphys_p2p';
	}
}
