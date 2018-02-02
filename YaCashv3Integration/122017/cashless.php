<?php
require_once(dirname(__FILE__) . "/base.php");

/**
 * Класс платежной системы Безналичный расчет
 */
class custom_paysystem_cashless extends custom_paysystem_base {

    /** @inheritdoc */
    public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject('cashless');
	}

    /**
     * @inheritdoc
     */
    public function getPaymentUrl($orderId) {
        return "/emarket/purchase/result/successful/";
    }

    /**
     * @inheritdoc
     */
    public function callback() {
        $buffer = outputBuffer::current();
        $buffer->clear();
        $buffer->contentType("text/html");
        $buffer->push('no callback for courier payment');
        $buffer->end();
        return;
    }

    /**
     * @inheritdoc
     */
    public function enabled() {
        return (boolean)$this->umiObject->getValue('active');
    }
};
?>