<?php
require_once __DIR__ . "/base.php";
use YandexCheckout\Client;
use YandexCheckout\Model\Confirmation\ConfirmationRedirect;
use YandexCheckout\Model\PaymentInterface;
use YandexCheckout\Common\Exceptions\UnauthorizedException;
use YandexCheckout\Common\Exceptions\ApiException;
use YandexCheckout\Common\Exceptions\BadApiRequestException;
use YandexCheckout\Common\Exceptions\InternalServerError;
use YandexCheckout\Common\Exceptions\TooManyRequestsException;
use YandexCheckout\Common\Exceptions\InvalidRequestException;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexCheckout\Request\Payments\CreatePaymentResponse;

/**
 * Базовый класс платежной системы Яндекс.Касса
 * Для тестирования платежной системы Яндекс . Касса, в конфигурационном файле сайта,
 * необходимо добавить секцию custom-yandex30 с ключом test
 */
class custom_paysystem_ymoney_base extends custom_paysystem_base {
	private $scid;
	private $shopid;
	private $password;
	/* @var string $cashboxShopId Идентификатор магазина API 3 версии */
	private $cashboxShopId;
	/* @var string $cashboxKey Секретный ключ магазина API 3 версии */
	private $cashboxKey;

	protected $choosePaysystemOnYandex;
	/* @var YandexCheckout\Client $checkoutClient объект класса интеграции с API 3 версии */
	protected $checkoutClient;
	/* @var boolean $isUseAPI3v флаг указывающий на режим работы с API 3 версии */
	protected $isUseAPI3v;

	const REQUEST_TYPE_AVISO = 'paymentAviso';
	const REQUEST_TYPE_CHECK = 'checkOrder';
	const REQUEST_TYPE_UNKNOWN = 'unknownRequest';

	const RESPONSE_CODE_SUCCESS 				= '0';
	const RESPONSE_CODE_AUTH_ERROR 				= '1';
	const RESPONSE_CODE_DECLINE_PAYMENT 		= '100';
	const RESPONSE_CODE_PARSE_REQUEST_ERROR 	= '200';

	const DESCRIBE_ERROR_WRONG_AMOUNT 		= 'wrongamount';
	const DESCRIBE_ERROR_NO_ORDER 			= 'noorder';
	const DESCRIBE_ERROR_UNKNOWN_CUSTOMER 	= 'unknowncustomer';
	const DESCRIBE_ERROR_OVERPAID 			= 'overpaid';
	const DESCRIBE_ERROR_ALREADY_PAID 		= 'alreadypaid';

	const PAYSYSTEM_ID_PREFIX = 'ymoney_';

	const PAYMENT_CREATE_ACTION = 'payment.create';
	const PAYMENT_CAPTURE_ACTION = 'payment.capture';
	const PAYMENT_CANCEL_ACTION = 'payment.cancel';

	public function __construct() {
		$this->scid                     = $this->getAuthorInfoField('yandex_scid_id');
		$this->shopid                   = $this->getAuthorInfoField('yandex_shop_id');
		$this->password                 = $this->getAuthorInfoField('yandex_shop_pass');
		$this->choosePaysystemOnYandex 	= (bool)$this->getAuthorInfoField('yandex_payment_yandex_choose');
		$this->isUseAPI3v               = (bool) $this->getAuthorInfoField('yandex_cashbox_use_api3v');
		$this->cashboxShopId            = (string) $this->getAuthorInfoField('yandex_cashbox_shop_id');
		$this->cashboxKey               = (string) $this->getAuthorInfoField('yandex_cashbox_key');
		if($this->isUseAPI3v) {
			$this->checkoutClient = new YandexCheckout\Client();
			$this->checkoutClient->setAuth($this->cashboxShopId, $this->cashboxKey);
		}
	}

	/** @inheritdoc */
	public function loadInfo() {
		$this->umiObject = $this->getPaysystemObject(self::PAYSYSTEM_ID_PREFIX . 'base');
	}

	/**
	 * @inheritdoc
	 */
	public function enabled() {
		$isShopParamsFull = $this->isUseAPI3v ? !empty($this->cashboxShopId) && !empty($this->cashboxKey) :
			!empty($this->scid) && !empty($this->shopid) && !empty($this->password);

		if ($this->choosePaysystemOnYandex && $isShopParamsFull) {
			return true;
		}

		if ($this->umiObject instanceof umiObject) {
			if ($this->umiObject->getValue('paysystem_id') == 'ymoney_base') {
				return false;
			}

			return (boolean)$this->umiObject->getValue('active') && $isShopParamsFull;
		}

		return false;
	}

	/**
	 * Проверяет валидность запроса путем проверки md5 сигнатуры
	 *
	 * @return boolean true - если запрос валиден, false в противном случае
	 */
	public function checkSignature() {
		if(!strlen($this->password)) return false;

		$hash_pieces   = array();
		$hash_pieces[] = getRequest('action');
		$hash_pieces[] = getRequest('orderSumAmount');
		$hash_pieces[] = getRequest('orderSumCurrencyPaycash');
		$hash_pieces[] = getRequest('orderSumBankPaycash');
		$hash_pieces[] = getRequest('shopId');
		$hash_pieces[] = getRequest('invoiceId');
		$hash_pieces[] = getRequest('customerNumber');
		$hash_pieces[] = $this->password;

		$hash_string   = md5(implode(';', $hash_pieces));

		return (strcasecmp($hash_string, getRequest('md5') ) == 0);
	}

	/**
	 * Отправка ответа платежной системе
	 *
	 * @param string $code
	 * @param string $response_type
	 * @param string $order_sum_amount
	 * @param string $message
	 */
	protected function sendResponse($code, $response_type = "", $order_sum_amount = "", $message = "") {
		$current_time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
		$date_time = date('c', $current_time);

		$buffer = outputBuffer::current();
		$buffer->clear();
		$buffer->contentType("text/xml");

		$response_element = null;

		$dom = new DOMDocument('1.0', 'UTF-8');

		if ($response_type == self::REQUEST_TYPE_CHECK) {
			$response_element = $dom->createElement('checkOrderResponse');
		} elseif ($response_type == self::REQUEST_TYPE_AVISO) {
			$response_element = $dom->createElement('paymentAvisoResponse');
		} else {
			$response_element = $dom->createElement('unknownResponse');
		}

		if ($response_element instanceof DOMElement == false) return;

		$response_element->setAttribute('performedDatetime', $date_time);
		$response_element->setAttribute('code', $code);
		$response_element->setAttribute('invoiceId', getRequest('invoiceId'));
		$response_element->setAttribute('shopId', getRequest('shopId'));
		if (!empty($order_sum_amount)) $response_element->setAttribute('orderSumAmount', number_format($order_sum_amount, 2, '.', ''));
		if (!empty($message)) $response_element->setAttribute('message', $message);

		$dom->appendChild($response_element);

		$buffer->push($dom->saveXML());
		$buffer->end();
	}

	/** @inheritdoc */
	public function getRequestType() {
		return 'POST';
	}

	/** @inheritdoc */
	public function getPaymentPostData($orderId) {
		if (!$orderId) {
			return array();
		}

		if ($this->isUseAPI3v) {
			return array('requestType' => 'GET');
		}

		$order = order::get($orderId);

		if (!$order) {
			return array();
		}

		$data = array(
			'scid' => $this->scid,
			'shopId' => $this->shopid,
			'sum' => $order->getActualPrice(),
			'customerNumber' => $order->getCustomerId(),
			'orderNumber' => $orderId,
			'paymentType' => $this->getPaymentType(),
			'cms_name' => 'umi'
		);

		$emarket = cmsController::getInstance()->getModule('emarket');
		if ($emarket instanceof emarket) {
			$receipt = new \UmiCms\Modules\Emarket\PaySystem\Yandex\YandexReceipt($order, $emarket);
			$data['ym_merchant_receipt'] = $receipt->__toString();
		}

		return $data;
	}



	/**
	 * Проверить включен ли тестовый режим
	 *
	 * @return bool
	 */
	protected function isTestMode() {
		return (bool)mainConfiguration::getInstance()->get('custom-yandex30', 'test');
	}

	/**
	 * Получить тип платежного способа
	 *
	 * @return string Возвращает двухзначный код платежного метода,
	 * подробнее см. https://money.yandex.ru/doc.xml?id=526537 таблица 6.4.1
	 */
	protected function getPaymentType() {
		if ($this->umiObject->getValue('paysystem_id') == 'ymoney_base') {
			return '';
		}

		$paysystem_without_prefix = str_replace(self::PAYSYSTEM_ID_PREFIX, '', $this->umiObject->getValue('paysystem_id'));
		return strtoupper($paysystem_without_prefix);
	}

	/**
	 * Подтверждение о выполнении платежа
	 */
	protected function paymentAviso() {
		if (!$this->checkSignature()) {
			$this->sendResponse(self::RESPONSE_CODE_AUTH_ERROR, self::REQUEST_TYPE_AVISO);
			return;
		}

		$order_id = getRequest('orderNumber');

		$order = order::get($order_id);

		if ($order instanceof order == false) {
			$this->sendResponse(self::RESPONSE_CODE_DECLINE_PAYMENT, self::REQUEST_TYPE_AVISO, "", $this->describeCode(self::DESCRIBE_ERROR_NO_ORDER));
			return;
		}

		$orderSum 	= $order->getActualPrice();
		$amount 	= floatval(getRequest('orderSumAmount'));

		if (order::getStatusByCode('accepted', 'order_payment_status') == $order->getPaymentStatus()) {
			$this->sendResponse(self::RESPONSE_CODE_SUCCESS, self::REQUEST_TYPE_AVISO, $amount, $this->describeCode(self::DESCRIBE_ERROR_ALREADY_PAID));
			return;
		}

		$overpaid = false;

		if (abs($orderSum - $amount) >= 0.01) { //отличаются на 1 копейку/цент или больше.
			if ($orderSum < $amount) {
				$overpaid = true;
				$this->sendOverpaidMessage($order_id);
			} else {
				$this->sendResponse(self::RESPONSE_CODE_DECLINE_PAYMENT, self::REQUEST_TYPE_AVISO, $amount, $this->describeCode(self::DESCRIBE_ERROR_WRONG_AMOUNT));
				return;
			}
		}

		$order->setOrderStatus('accepted');
		$order->setPaymentStatus('accepted');
		$order->setValue('payment_document_num', getRequest('invoiceId'));
		$order->commit();

		$message = '';

		if ($overpaid) $message = $this->describeCode(self::DESCRIBE_ERROR_OVERPAID);

		$this->sendResponse(self::RESPONSE_CODE_SUCCESS, self::REQUEST_TYPE_AVISO, $amount, $message);
	}

	/**
	 * Подтверждение выполнения платежа
	 * для API Яндекс-кассы 3 версии
	 *
	 * @param YandexCheckout\Model\PaymentInterface  $payment Объект платежа, передаваемый в
	 * нотификации API при его подтверждении
	 *
	 * @return mixed Результат подтверждения
	 */
	protected function capturePayment(YandexCheckout\Model\PaymentInterface $payment) {
		if (!$this->checkSignatureAPIv3($payment)) {
			return $this->cancelPayment($payment, getLabel('yandex-api-order-hash-err', 'emarket'));
		}

		$paymentMetadata = ($metadataObject = $payment->getMetadata()) ?
			$metadataObject->toArray() : array();

		$orderId = (int) $paymentMetadata['orderNumber'];
		$order   = order::get($orderId);

		if ($order instanceof order == false) {
			return $this->cancelPayment($payment, getLabel('yandex-api-order-not-found', 'emarket'));
		}

		$orderSum = $order->getActualPrice();
		$amount   = floatval($payment->getAmount()->getValue());

		if (order::getStatusByCode('accepted', 'order_payment_status') == $order->getPaymentStatus()) {
			return $this->cancelPayment( $payment, getLabel('yandex-api-order-already-paid', 'emarket'));
		}

		if (abs($orderSum - $amount) >= 0.01) {
			return $this->cancelPayment($payment, getLabel('yandex-api-prices-mismatch', 'emarket'));
		}

		$order->setOrderStatus('accepted');
		$order->setPaymentStatus('accepted');
		$order->setValue('payment_document_num', $payment->getId());
		$order->commit();

		return $this->processingPayment($payment, self::PAYMENT_CAPTURE_ACTION);
	}

	/**
	 * Осуществляет отмену и формирует массив данных, возвращаемых
	 * при отмене при подтверждении
	 *
	 * @param YandexCheckout\Model\PaymentInterface $payment Объект платежа
	 * @param string $cancelReason Текст причины отмены
	 *
	 * @return array
	 */
	function cancelPayment(YandexCheckout\Model\PaymentInterface $payment, $cancelReason) {
		$apiResult = $this->processingPayment($payment, self::PAYMENT_CANCEL_ACTION);
		return array_merge( is_array($apiResult) ? $apiResult : array(),
			array('cancel' => $cancelReason) );
	}

	/**
	 * Выполнение одной из операций над платежом
	 * в технологии API Яндекс-кассы 3 версии
	 *
	 * @param mixed $payment Объект платежа, возвращаемого при его подтверждении
	 * @param string $action Тип запроса - создание, подтверждение или отмена
	 * @param mixed $order Объект заказа, опциональный, необходим при создании заказа
	 *
	 * @return mixed Результат обращения к API
	 */
	protected function processingPayment($payment, $action, order $order = NULL) {
		try {

			if (strcmp($action, self::PAYMENT_CREATE_ACTION) == 0 && isset($order)) {
				$domain = $order->getDomain();
				if (!$domain) {
					$domain = domainsCollection::getInstance()->getDefaultDomain();
				}

				if (!$domain) {
					return array('error' => getLabel('yandex-api-domain-name-err', 'emarket'));
				}

				$hashPieces   = array();
				$hashPieces[] = $order->getId();
				$hashPieces[] = $order->getCustomerId();
				$hashPieces[] = ceil($order->getActualPrice());
				$hashPieces[] = $this->cashboxShopId;
				$hashPieces[] = $this->cashboxKey;

				return $this->checkoutClient->createPayment(
					array_merge(
						array(
							'amount' => array(
								'value'    => $order->getActualPrice(),
								'currency' => 'RUB'
							),
							'confirmation'   => array(
								'type'       => 'redirect',
								'return_url' => 'http://' . $domain->getHost() . '/emarket/purchase/result/done/' .
									'?order_id=' . $order->getId(),
							),
							'metadata' => array(
								'customerNumber' => $order->getCustomerId(),
								'orderNumber'    => $order->getId(),
								'cms_name'       => 'umi',
								'md5'            => md5(implode(';', $hashPieces))
							),
						),
						$this->getPaymentMethodData($order)
					),
					uniqid('', true)
				);
			}

			if (!($payment instanceof YandexCheckout\Model\PaymentInterface)) {
				return array('error' => getLabel('yandex-api-payment-uncorrect-obj', 'emarket'));
			}

			if (strcmp($action, self::PAYMENT_CAPTURE_ACTION) == 0 && isset($payment)) {
				$response = $this->checkoutClient->capturePayment(
					array(
						'amount'   => $payment->getAmount()->getValue(),
						'currency' => 'RUB'
					),
					$payment->getId(),
					uniqid('', true)
				);


				if (($responseStatus = $response->getStatus()) === 'succeeded') {
					return array('success' => getLabel('yandex-api-payment-captured-success', 'emarket'));
				} else {
					return array('error'   => getLabel('yandex-api-payment-captured-err', 'emarket') .
						' Статус: ' . $responseStatus);
				}
			}

			if (strcmp($action, self::PAYMENT_CANCEL_ACTION) == 0 && isset($payment)) {
				$response = $this->checkoutClient->cancelPayment(
					$payment->getId(),
					uniqid('', true)
				);

				if (($responseStatus = $response->getStatus()) === 'canceled') {
					return array('success' => getLabel('yandex-api-payment-cancel-success', 'emarket'));
				} else {
					return array('error'   => getLabel('yandex-api-payment-cancel-err', 'emarket') .
						'Статус: ' . $responseStatus);
				}
			}

		} catch (YandexCheckout\Common\Exceptions\UnauthorizedException $uatex) {
			return array('error' => getLabel('yandex-api-auth-error', 'emarket'));
		} catch (YandexCheckout\Common\Exceptions\BadApiRequestException $barex) {
			$exceptionMsg = $barex->getMessage();
			$errMsg = getLabel('yandex-api-payment-create-error', 'emarket') . $exceptionMsg;

			if (is_int(stripos($exceptionMsg, 'Payment method is not available'))) {
				$errMsg = getLabel('yandex-api-payment-not-available', 'emarket');
			} else if (is_int(stripos($exceptionMsg, 'Invalid request parameter'))) {
				$errMsg = getLabel('yandex-api-payment-missing-params', 'emarket');
			}

			return array('error' => $errMsg);
		} catch (YandexCheckout\Common\Exceptions\InternalServerError $isex) {
			return array('error' => getLabel('yandex-api-server-error', 'emarket') . $isex->getMessage());
		}  catch (YandexCheckout\Common\Exceptions\TooManyRequestsException $tmrex) {
			return array('error' => getLabel('yandex-api-too-many-requests', 'emarket'));
		} catch (YandexCheckout\Common\Exceptions\ApiException $apiex) {
			return array('error' => getLabel('yandex-api-not-correct-request', 'emarket') . $apiex->getMessage());
		} catch (YandexCheckout\Common\Exceptions\InvalidRequestException $irex) {
			return array('error' => getLabel('yandex-api-bad-request', 'emarket') . $irex->getMessage());
		} catch (Exception $ex) {
			return array('error' => getLabel('yandex-api-payment-create-error', 'emarket') . $ex->getMessage());
		}

		return false;
	}

	/**
	 * Проверяет валидность запроса путем проверки md5 сигнатуры
	 * вариант для 3 версии API
	 *
	 * @param YandexCheckout\Model\PaymentInterface $payment Объект платежа
	 *
	 * @return boolean true - если запрос валиден, false в противном случае
	 */
	public function checkSignatureAPIv3(YandexCheckout\Model\PaymentInterface $payment) {
		if(!strlen($this->cashboxKey)) return false;

		$paymentMetadata = ($metadataObject = $payment->getMetadata()) ?
			$metadataObject->toArray() : array();
		$md5 = (string) $paymentMetadata['md5'];

		$hashPieces   = array();
		$hashPieces[] = (string) $paymentMetadata['orderNumber'];
		$hashPieces[] = (string) $paymentMetadata['customerNumber'];
		$hashPieces[] = ceil(floatval($payment->getAmount()->getValue()));
		$hashPieces[] = $this->cashboxShopId;
		$hashPieces[] = $this->cashboxKey;

		$hashString   = md5(implode( ';', $hashPieces));

		return strcasecmp($hashString, $md5) == 0 && strlen($md5) > 0;
	}

	/**
	 * Возвращает признак использования API 3 версии
	 *
	 * @return boolean
	 */
	public function getIsUseAPI3v() {
		return $this->isUseAPI3v;
	}

	/**
	 * Проверка заказа перед оплатой
	 */
	protected function checkOrder() {
		$order_id = getRequest('orderNumber');
		$customer_number = getRequest('customerNumber');

		$order = order::get($order_id);
		if(!$order) {
			$this->sendResponse(self::RESPONSE_CODE_DECLINE_PAYMENT, self::REQUEST_TYPE_CHECK, "", $this->describeCode(self::DESCRIBE_ERROR_NO_ORDER));
			return;
		}

		if ($order->getCustomerId() != $customer_number) {
			$this->sendResponse(self::RESPONSE_CODE_DECLINE_PAYMENT, self::REQUEST_TYPE_CHECK, "", $this->describeCode(self::DESCRIBE_ERROR_UNKNOWN_CUSTOMER));
		}

		if (!$this->checkSignature()) {
			$this->sendResponse(self::RESPONSE_CODE_AUTH_ERROR, self::REQUEST_TYPE_CHECK);
			return;
		}

		$orderSum 	= $order->getActualPrice();
		$amount 	= floatval(getRequest('orderSumAmount'));

		$overpaid = false;

		if (abs($orderSum - $amount) >= 0.01) { //отличаются на 1 копейку/цент или больше.
			if ($orderSum < $amount) {
				$overpaid = true;
				$this->sendOverpaidMessage($order_id);
			} else {
				$this->sendResponse(self::RESPONSE_CODE_DECLINE_PAYMENT, self::REQUEST_TYPE_CHECK, $amount, $this->describeCode(self::DESCRIBE_ERROR_WRONG_AMOUNT));
				return;
			}
		}

		$message = '';

		if ($overpaid) $message = $this->describeCode(self::DESCRIBE_ERROR_OVERPAID);

		$this->sendResponse(self::RESPONSE_CODE_SUCCESS, self::REQUEST_TYPE_CHECK, $amount, $message);
	}

	/**
	 * @inheritdoc
	 */
	public function callback() {
		if ($this->isUseAPI3v) {

			if ( strlen( $requestBody = (string) file_get_contents('php://input') ) == 0) die('Missing params!');
			$notificationData = json_decode($requestBody, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				die(getLabel('yandex-api-error-json-parsing', 'emarket'));
			}
			$logMsg        = getLabel('yandex-api-payment-captured-success', 'emarket');
			$captureResult = false;

			try {

				$notificationWaitForCapture = new YandexCheckout\Model\Notification\NotificationWaitingForCapture(
					$notificationData
				);
				$captureResult = $this->capturePayment($payment = $notificationWaitForCapture->getObject());
				$logMsg        = 'capture payment, id=' . $payment->getId() . ', captureResult=' .
					var_export( $captureResult, true) . ', requestBody=' . $requestBody;

			} catch (YandexCheckout\Common\Exceptions\ApiException $apiex) {
				$logMsg = getLabel('yandex-api-not-correct-request', 'emarket') . $apiex->getMessage();
			} catch (YandexCheckout\Common\Exceptions\InvalidRequestException $irex) {
				$logMsg = getLabel('yandex-api-bad-request', 'emarket') . $irex->getMessage();
			} catch (Exception $ex) {
				$logMsg = getLabel('yandex-api-bad-payment-capture', 'emarket') . $ex->getMessage();
			}

			if (is_array($captureResult) && (isset($captureResult['error']) || isset($captureResult['cancel'])) ||
				$captureResult === false) {
				umihost_system_logger::getInstance()->addLog(__CLASS__, $logMsg,
					umihost_system_logger::LEVEL_ERROR);
			}

			die($logMsg);
		}

		$action = getRequest('action');

		if ($action == self::REQUEST_TYPE_CHECK) {
			$this->checkOrder();
			return;
		} elseif ($action == self::REQUEST_TYPE_AVISO) {
			$this->paymentAviso();
			return;
		}

		$this->sendResponse(self::RESPONSE_CODE_PARSE_REQUEST_ERROR);
		exit();
	}

	/**
	 * @inheritdoc
	 */
	function getPaymentUrl($orderId) {
		if ( $this->isTestMode() ) {
			return 'https://demomoney.yandex.ru/eshop.xml';
		}

		if ($this->isUseAPI3v) {
			if (!$orderId) {
				return '';
			}

			$order        = order::get($orderId);
			$confirmation = false;

			$mixedAPIResult = $this->processingPayment(NULL, self::PAYMENT_CREATE_ACTION, $order);
			if ($mixedAPIResult instanceof YandexCheckout\Request\Payments\CreatePaymentResponse) {
				$confirmation = $mixedAPIResult->getConfirmation();
				return $confirmation instanceof YandexCheckout\Model\Confirmation\ConfirmationRedirect ?
					$confirmation->getConfirmationUrl() : '';
			}

			return $mixedAPIResult;
		}

		return 'https://money.yandex.ru/eshop.xml';
	}

	/**
	 * Получить текстовое описание кода сообщения
	 *
	 * @param string $code Код сообщения
	 *
	 * @return string
	 */
	protected function describeCode($code) {
		$message = '';

		switch($code) {
			case self::DESCRIBE_ERROR_WRONG_AMOUNT:
				$message = getLabel('yandex-order-sum-not-correct', 'emarket');
				break;
			case self::DESCRIBE_ERROR_OVERPAID:
				$message = getLabel('yandex-overpaid-order', 'emarket');
				break;
			case self::DESCRIBE_ERROR_NO_ORDER:
				$message = getLabel('yandex-order-not-found', 'emarket');
				break;
			case self::DESCRIBE_ERROR_UNKNOWN_CUSTOMER:
				$message = getLabel('yandex-customer-not-found', 'emarket');
				break;
			case self::DESCRIBE_ERROR_ALREADY_PAID:
				$message = getLabel('yandex-order-already-paid', 'emarket');
				break;
		}

		return $message;
	}

	/**
	 * Возвращает массив параметров для метода оплаты при создании платежа
	 *
	 * @param order $order Объект заказа для извлечения кастомных параметров
	 *
	 * @return array
	 */
	protected function getPaymentMethodData(order $order) {
		$typeCode = $this->getPaymentTypeCode();
		return $typeCode ? array(
			'payment_method_data' =>
				array_merge(
					array(
						'type' => $typeCode
					),
					$this->getPaymentCustomParams($order)
				)
			) : array();
	}

	/**
	 * Возвращает строковый идентификатор (код) типа оплаты
	 * или false в случае его отсутствия
	 *
	 * @return mixed
	 */
	protected function getPaymentTypeCode() {
		return false;
	}

	/**
	 * Возвращает массив кастомных параметров
	 * передаваемый при использовании отдельного метода оплаты
	 *
	 * @param order $order Объект заказа для извлечения кастомных параметров
	 *
	 * @return array
	 */
	protected function getPaymentCustomParams(order $order) {
		return array();
	}
}