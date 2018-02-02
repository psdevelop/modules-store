<?php
abstract class custom_paysystem_base {
	/**
	* Object of paysystem in UMI.CMS
	* @var umiObject 
	*/
	protected $umiObject;
	
	/**
	 * Object of author info page
	 * @var umiObject 
	 */
	protected $authorInfoPage;

	/** Загружает информацию о платежной системы из внешнего источника */
	abstract public function loadInfo();

	/**
	 * Получить объект платежной системы из справочника
	 *
	 * @param int $paysystem_id идентификатор платежной системы
	 *
	 * @return umiObject|null
	 */
	protected function getPaysystemObject($paysystem_id) {
		$guides_list = umiObjectTypesCollection::getInstance()->getGuidesList();
		$type_id = false;
		foreach($guides_list as $guide_id => $guide_name) {
			if(strcmp($guide_name, "Способы оплаты заказа сайта") == 0) {
				$type_id = $guide_id;
				break;
			}
		}

		$object = null;

		if($type_id) {
			$sel = new selector('objects');
			$sel->types('object-type')->id($type_id);
			$sel->where('paysystem_id')->equals($paysystem_id);
			$object = $sel->first;
		}

		return $object;
	}
	
	/**
	* Get umi object Id 
	* @return int
	*/
	public function getId() {
		if($this->umiObject instanceof umiObject) {
			return $this->umiObject->getId();
		}
		return null;
	}

	/**
	* Get readable caption of payment system
	* @return string
	*/
	public function getName() {
		if($this->umiObject instanceof umiObject) {
			return $this->umiObject->getName();
		}
		return null;
	}

	/**
	* Get paysystem identifier
	* @return string
	*/
	public function getIdentificator() {
		if($this->umiObject instanceof umiObject) {
			return $this->umiObject->getValue('paysystem_id');
		}
		return null;
	}

	/**
	 * Get value of field from author info page
	 * @param string $fieldName Field name
	 * @return mixed 
	 */
	public function getAuthorInfoField($fieldName) {
		if($this->authorInfoPage instanceof umiObject == false) {
			$iTypeId = false;
			$oHierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName('content', '');
			$arTypes = umiObjectTypesCollection::getInstance()->getTypesByHierarchyTypeId($oHierarchyType->getId());
			foreach($arTypes as $typeId => $sTypeName) {
				if ($sTypeName == "Авторская информация") {
					$iTypeId = $typeId;
					break;
				}
			}
			if(!$iTypeId) return null;

			$sel = new selector('objects');
			$sel->types('object-type')->id($iTypeId);
			$sel->limit(0, 1);
			$object = $sel->first;
			if(!$object) return null;
			$this->authorInfoPage = $object;
		}
		
		return $this->authorInfoPage->getValue($fieldName);
	}
	
	/**
	 * Check if this paysystem available and enabled
	 * @return boolean 
	 */
	abstract function enabled();

	/**
	 * Callback for payment system
	 * @return mixed
	 */
	abstract function callback();

	/**
	 * Return payment request type.
	 * 
	 * @return string GET or POST.
	 */
	public function getRequestType() {
		return 'GET';
	}

	/**
	* Get URL for payment for order
	* @param int $orderId ID of order for pay
	* @return mixed
	*/
	abstract function getPaymentUrl($orderId);

	/**
	 * Return post data if payment type is POST.
	 *
	 * @param int $orderId ID of order for pay 
	 * @return array Post data.
	 */
	public function getPaymentPostData($orderId) {
		return array();
	}

	/**
	 * Обработчик события выбора способа оплаты
	 * @param order $order оплачиваемый заказ
	 */
	public function onChoose(order $order) {}

	/**
	 * Sends message to site manager if order was overpaid
	 *
	 * @param int $orderId ID of order to send
	 */
	public function sendOverpaidMessage($orderId) {
		$oOrder = order::get($orderId);
		$regedit = regedit::getInstance();

		$email_to = $regedit->getVal("//modules/emarket/manager-email");
		$email_from = $regedit->getVal("//modules/emarket/from-email");
		$fio_from = $regedit->getVal("//modules/emarket/from-name");

		$domain = $oOrder->getDomain();
		if(!$domain) {
			$domain = domainsCollection::getInstance()->getDefaultDomain();
		}
		$domain_id = $domain->getId();
		if( (method_exists('regedit', 'onController') && regedit::onController() && !$domain->getIsDefault()) || // Если на контроллере но на недефолтном домене
			!method_exists('regedit', 'onController') || !regedit::onController()) { // Или не на контроллере
			// То проверить сначала значение с id домена. Если оно есть - использовать его.
			$emailDomain = $regedit->getVal("//modules/emarket/manager-email/{$domain_id}");
			if(!empty($emailDomain)) $email_to = $emailDomain;

			$fromEmailDomain = strtolower($regedit->getVal("//modules/emarket/from-email/{$domain_id}"));
			if(!empty($fromEmailDomain)) $email_from = $fromEmailDomain;

			$fromNameDomain = $regedit->getVal("//modules/emarket/from-name/{$domain_id}");
			if(!empty($fromNameDomain)) $fio_from = $fromNameDomain;
		}

		$uri = "udata://emarket/order/{$orderId}/?transform=sys-tpls/overpaid-mail.xsl";
		$mailContent = file_get_contents($uri);

		$email = preg_replace('/[\s;,]+/', ",", $email_to);
		$arEmails = explode(",", $email);
		$letter = new umiMail();
		foreach ($arEmails as $sEmail) {
			$letter->addRecipient(strtolower($sEmail));
		}
		$letter->setFrom($email_from, $fio_from);
		$letter->setSubject(getLabel('overpaid-mail-header', 'emarket'));
		$letter->setContent($mailContent);
		$letter->commit();
		$letter->send();
	}

	/**
	 * Возвращает объект платежной системы
	 * @param string $name кодовое имя платежной системы
	 * @return custom_paysystem_base
	 * @throws ErrorException если платежная система не существует
	 */
	public static function getPaysystem($name) {
		$filePath = __DIR__ . "/{$name}.php";

		if ( !file_exists($filePath) ) {
			throw new ErrorException( getLabel('error-market-custom-no-such-paysystem', 'emarket') );
		}

		require_once($filePath);

		$class = "custom_paysystem_{$name}";

		if ( !class_exists($class) ) {
			throw new ErrorException( getLabel('error-market-custom-no-such-paysystem', 'emarket') );
		}

		return new $class();
	}

	/**
	 * Выводит ответ и прекращает выполнение скрипта
	 * @param string $response текст ответа
	 * @return bool
	 */
	public static function pushResponse($response) {
		$buffer = outputBuffer::current();
		$buffer->clear();
		$buffer->contentType("text/html");
		$buffer->push($response);
		$buffer->end();
		return true;
	}
}
