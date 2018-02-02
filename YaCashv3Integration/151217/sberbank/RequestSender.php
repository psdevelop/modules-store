<?php

namespace UmiCms\Modules\Emarket\PaySystem\Sberbank;

/**
 * Отправитель запросов
 * Class RequestSender
 * @package UmiCms\Modules\Emarket\PaySystem\Sberbank
 */
class RequestSender {
	/** @var string URL-адрес запроса */
	private $url;
	/** @var array параметры запроса */
	private $params;

	/**
	 * Выполняет запрос
	 * @return bool|string false в случае если ответ не был получен или ответ на запрос в ином случае
	 */
	public function request() {
		$fullUrl = $this->url . '?' . http_build_query($this->params);
		return file_get_contents($fullUrl);
	}

	/**
	 * Возвращает @see RequestSender::$url
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Возвращает параметры запроса
	 * @return array
	 */
	public function getRequestParams() {
		return $this->params;
	}

	/**
	 * Устанавливает @see RequestSender::$url
	 * @param $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * Устанавливает параметры запроса
	 * @param array $params список параметров
	 */
	public function setRequestParams(array $params) {
		$this->params = $params;
	}

}