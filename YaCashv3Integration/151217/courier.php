<?php
require_once(dirname(__FILE__) . "/base.php");

/**
 * Класс платежной системы Наличными курьеру
 */
class custom_paysystem_courier extends custom_paysystem_base {

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject('courier');
	}
	
	public function getPaymentUrl($orderId) {
		return "/emarket/purchase/result/successful/";
	}

	public function callback() {
		$buffer = outputBuffer::current();
		$buffer->clear();
		$buffer->contentType("text/html");
		$buffer->push('no callback for courier payment');
		$buffer->end();
		return;
	}
	
	public function enabled() {
		return (boolean)$this->umiObject->getValue('active');
	}
};
?>