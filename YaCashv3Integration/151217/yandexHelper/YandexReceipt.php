<?php

namespace UmiCms\Modules\Emarket\PaySystem\Yandex;

/**
 * Отвечает за формирование данных для чека в Яндекс.Касса
 * @link https://tech.yandex.ru/money/doc/payment-solution/payment-form/payment-form-receipt-docpage/
 * Class YandexReceipt
 * @package UmiCms\Modules\Emarket\PaySystem\Yandex
 */
class YandexReceipt {
	/** @const кодовое имя валюты по умолчанию */
	const DEFAULT_CURRENCY = "RUB";

	/** @var \order заказ */
	private $order;
	/** @var \emarket|\__emarket_custom модуль "Интернет-магазин" */
	private $emarket;

	/**
	 * YandexReceipt constructor.
	 * @param \order $order заказ
	 * @param \emarket $emarket модуль "Интернет-магазин"
	 */
	public function __construct(\order $order, \emarket $emarket) {
		$this->order = $order;
		$this->emarket = $emarket;
	}

	/**
	 * Возвращает сериализованные данные для чека
	 * @return string
	 */
	public function __toString() {
		$data = array();

		$contact = $this->getCustomerContact();
		if ($contact) {
			$data['customerContact'] = $contact;
		}

		$data['items'] = $this->getItems();

		return json_encode($data);
	}

	/**
	 * Возвращает контакт покупателя (телефон или e-mail)
	 * @return string
	 */
	private function getCustomerContact() {
		try {
			$customer = $this->order->getCustomer();
			return $customer->getEmail();
		} catch (\Exception $e) {
			return '';
		}
	}

	/**
	 * Возвращает данные для товаров
	 * @return array
	 */
	private function getItems() {
		$items = array();

		foreach ($this->order->getItems() as $item) {
			$items[] = $this->prepareItem($item);
		}

		if ( $this->order->getDelivery() ) {
			$items[] = $this->prepareItem( $this->order->getDelivery() );
		}

		return $items;
	}

	/**
	 * Подготавливает и возвращает данные товара
	 * @param \IOrderItem $item товар в заказе
	 * @return array
	 */
	private function prepareItem(\IOrderItem $item) {
		return array(
			'quantity' => sprintf( '%.3f', $item->getAmount() ),

			'price' => array(
				'amount' => sprintf( '%.2f', $item->getTotalActualPrice() ),
				'currency' => $this->getCurrency()
			),

			'tax' => $item->getNdsRate()->toYandexId(),
			'text' => $item->getName()
		);
	}

	/**
	 * Возвращает кодовое имя выбранной валюты на сайте
	 * @return mixed|string
	 */
	private function getCurrency() {
		try {
			$code = $this->emarket->getSelectedCurrency()->getValue('codename');
			return ($code === "RUR" ? "RUB" : $code); // Яндекс ожидает "RUB", в системе "RUR"
		} catch (\Exception $e) {
			return self::DEFAULT_CURRENCY;
		}
	}
}