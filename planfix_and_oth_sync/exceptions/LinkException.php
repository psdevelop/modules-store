<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 29.08.17
 * Time: 18:01
 */

namespace app\exceptions;

use yii\base\UserException;

class LinkException extends UserException
{
    const ERROR_HEADER_INVALID_HANDLER = "Некорректный обработчик";
    const ERROR_HEADER_INVALID_QUERY = "Некорректный формат запроса";
    const ERROR_HEADER_HTTP_METHOD = "Недопустимый метод HTTP";
    const ERROR_HEADER_ACCESS = "Нет доступа";
    const ERROR_HEADER_NOT_FOUND = "404";

    protected $header;
    protected $errors;
    protected $helpView;

    /**
     * LinkException constructor.
     * @param string $message
     * @param string $header
     * @param array $errors
     * @param string $helpView
     * @param int $code
     */
    public function __construct($message = "", $header = "", $errors = [], $helpView = null, $code = 0)
    {
        $this->setHeader($header);
        $this->setErrors($errors);
        $this->setHelpView($helpView);
        parent::__construct($message, $code);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $errors
     * @return mixed
     */
    public function setErrors($errors)
    {
        return $this->errors = $errors;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param $header
     * @return string
     */
    public function setHeader($header)
    {
        return $this->header = $header;
    }

    /**
     * @return mixed
     */
    public function getHelpView()
    {
        return $this->helpView ?? null;
    }

    /**
     * @param $helpView
     * @return mixed
     */
    public function setHelpView($helpView = null)
    {
        return $this->helpView = $helpView;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->header;
    }

    /**
     * @param $errors
     * @param $message
     * @param $helpView
     * @throws LinkException
     */
    public static function validationError($message = null, $errors, $helpView = null)
    {
        throw new LinkException($message ?? "Запрос сосатвлен неверно...", self::ERROR_HEADER_INVALID_QUERY, $errors, $helpView);
    }

    /**
     * @param $errors
     * @param $method
     * @param $helpView
     * @throws LinkException
     */
    public static function validationHttpMethod($method, $errors = [], $helpView = null)
    {
        throw new LinkException("$method - Недопустимый метод для данного модуля...", self::ERROR_HEADER_HTTP_METHOD, $errors, $helpView);
    }

    /**
     * @param $errors
     * @param $message
     * @param $helpView
     * @throws LinkException
     */
    public static function systemError($message, $errors = [], $helpView = null)
    {
        throw new LinkException($message, 500, $errors, $helpView);
    }

    public static function notFound($message)
    {
        throw new LinkException(
            $message ?? "Объект не найден",
            LinkException::ERROR_HEADER_NOT_FOUND,
            [],
            null,
            404
        );
    }
}
