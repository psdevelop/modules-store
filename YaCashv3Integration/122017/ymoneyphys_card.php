<?php
require_once __DIR__ . '/ymoneyphys_base.php';

/**
 * Класс платежной системы Яднекс.Деньги для физических лиц.
 *
 * Оплата через карту.
 */
class custom_paysystem_ymoneyphys_card extends custom_paysystem_ymoneyphys_base {
	/**
	 * {@inheritdoc}
	 */
	protected function getPaysystemType() {
		return 'AC';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getPaysystemGuide() {
		return 'ymoneyphys_card';
	}
}
