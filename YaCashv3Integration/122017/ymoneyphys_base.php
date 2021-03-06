<?php
require_once __DIR__ . '/base.php';

/**
 * Класс платежной системы Яднекс.Деньги для физических лиц.
 *
 * Общий класс
 */
abstract class custom_paysystem_ymoneyphys_base extends custom_paysystem_base {

	/**
	 * Номер кошелька магазина.
	 * 
	 * @var string
	 */
	private $account;

	/**
	 * Секретное слово для проверки уведомлений об оплате.
	 * 
	 * @var string
	 */
	private $secret;

	public function __construct() {
		$this->account = $this->getAuthorInfoField('ymoneyphys_account');
		$this->secret = $this->getAuthorInfoField('ymoneyphys_secret');
	}

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject( $this->getPaysystemGuide() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestType() {
		return 'POST';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPaymentUrl($orderId) {
		return 'https://money.yandex.ru/quickpay/confirm.xml';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getPaymentPostData($orderId) {
		$order = order::get($orderId);

		if (!$order) {
			return array();
		}

		return array(
			'receiver' => $this->account,
			'formcomment' => getLabel('ymoneyphys-formcomment', 'emarket', $order->getId()),
			'short-dest' => getLabel('ymoneyphys-short-dest', 'emarket', $order->getId()),
			'quickpay-form' => 'small',
			'targets' => getLabel('ymoneyphys-target', 'emarket', $order->getId(), $this->getDomainName($order)),
			'sum' => $order->getActualPrice(),
			'paymentType' => $this->getPaysystemType(),
			'label' => $order->getId(),
			'successURL' => 'http://' . $_SERVER['SERVER_NAME'] . '/emarket/purchase/result/successful/'
		);
	}

	/**
	 * Получить тип платежного способа.
	 *
	 * @return string Возвращает двухзначный код платежного метода.
	 */
	abstract protected function getPaysystemType();

	/**
	 * Получить имя справочника платежной системы.
	 *
	 * @return string Системное имя справочника.
	 */
	abstract protected function getPaysystemGuide();

	/**
	 * {@inheritdoc}
	 */
	public function callback() {
		$notification_type 	= getRequest('notification_type');  // Для переводов из кошелька — p2p-incoming. Для переводов с произвольной карты — cardincoming.
		$operation_id		= getRequest('operation_id');		// Идентификатор операции в истории счета получателя.
		$amount 			= getRequest('amount');				// Сумма, которая зачислена на счет получателя.
		$withdraw_amount 	= getRequest('withdraw_amount');	// Сумма, которая списана со счета отправителя.
		$currency 			= getRequest('currency');			// Код валюты — всегда 643 (рубль РФ согласно ISO 4217).
		$datetime 			= getRequest('datetime');			// Дата и время совершения перевода.
		$sender 			= getRequest('sender');				// Для переводов из кошелька — номер счета отправителя. Для переводов с произвольной карты п араметр содержит пустую строку.
		$codepro 			= getRequest('codepro');			// Для переводов из кошелька — перевод защищен кодом протекции. Для переводов с произвольной карты — всегда false.
		$label 				= getRequest('label');				// Метка платежа (Номер заказа). Если ее нет, параметр содержит пустую строку.
		$sha1 				= getRequest('sha1_hash');			// SHA-1 hash параметров уведомления.

		$notification_secret = $this->secret; 					// Секретное слово для проверки уведомлений
		
		$response = function ($status) {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->status($status);
			$buffer->end();	
		};
		
		// Удостоверение подлинности и целостности уведомления
		if ($sha1 === sha1("$notification_type&$operation_id&$amount&$currency&$datetime&$sender&$codepro&$notification_secret&$label")) {
			
			$order = order::get($label);
			
			// Проверяем есть ли такой заказ.			
			if (!$order) {
				$response('404 Not Found');	
			}
			
			// Проверяем код валюты. Всегда должен быть 643.
			if ($currency !== '643') {
				$response('400 Bad Request');
			}
			
			// Проверяем что заказ не защищен кодом протекции. 
			if ($codepro !== 'false') {
				$response('400 Bad Request');
			}

			// Проверяем что сумма заказа совтадает с заплаченной суммой клиентом с точностью до одной копейки.
			// Если сумма отличается в меньшую сторону откланяем запрос, если в большую то посылаем письмо менеджеру с оповещением.
			$withdraw_amount = floatval($withdraw_amount);
			if (abs($order->getActualPrice() - $withdraw_amount) >= 0.01) { //отличаются на 1 копейку/цент или больше.
				if ($order->getActualPrice() < $withdraw_amount) {
					$this->sendOverpaidMessage($order->getId());
				} else {
					$response('400 Bad Request');
				}
			}

			// Если заказ уже отмечен как оплаченный, то сообщяем что все хорошо.
			if (order::getStatusByCode('accepted', 'order_payment_status') == $order->getPaymentStatus()) {
				$response('200 OK');
			}
			
			// Все проверки прошли успешно, отмечаем заказ как успешно оплаченный.
			$order->setOrderStatus('accepted');
			$order->setPaymentStatus('accepted');
			$order->setValue('payment_document_num', $operation_id);
			$order->commit();
			
			// Уведомление считается принятым, если ответили на запрос кодом HTTP 200 OK.
			$response('200 OK');
			
		} else {
			$response('403 Forbidden');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function enabled() {
		if ($this->umiObject instanceof umiObject) {
			return (boolean)$this->umiObject->getValue('active');
		} else {
			return false;
		}
	}

	/**
	 * Возвращает доменное имя.
	 * 
	 * @param order $order
	 * @return string
	 */
	private function getDomainName($order) {
		$domainName = false;
		if(method_exists('regedit', 'onController') && regedit::onController()) {
			$host = regedit::getControllerHost(true);
			if ($host instanceof umihost_system_host) {
				$domainName = $host->getDomainName(true);
			}
		}

		if (!$domainName) {
			$domain = domainsCollection::getInstance()->getDomain($order->domain_id);
			if ($domain) {
				$domainName = $domain->getHost();
			}
		}
		
		return $domainName;
	}
}
