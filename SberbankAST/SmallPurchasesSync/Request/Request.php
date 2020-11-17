<?php

namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Request;

use App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces\IRequest;

/**
 * Class Request
 * @package App\Service\ETP\SberbankAST\SmallPurchasesSync\Request
 * @property $client HttpClient
 *
 * @author Poltarokov SP
 * 10.08.2020
 */
abstract class Request implements IRequest
{
    const MULTIPART_FORM_DATA = 1;
    const URL_ENCODED_FORM = 2;

    /** @var string $importSubSource */
    protected $importSubSource;

    /** @var Filter $filter */
    protected $filter;

    /** @var string $url */
    protected $url;

    /** @var int $formDataType */
    protected $formDataType = self::URL_ENCODED_FORM;

    /** @var array $headers */
    protected $headers;

    public function __construct()
    {
        $this->filter = new Filter();
    }

    /**
     * @inheritDoc
     */
    abstract public function getMethod(): string;

    /**
     * @inheritDoc
     */
    abstract public function getPostFields(): array;

    /**
     * @inheritDoc
     */
    abstract public function getUrl(): string;

    /**
     * @inheritDoc
     */
    public function getImportSubSource(): string
    {
        return $this->importSubSource;
    }

    /**
     * @inheritDoc
     */
    public function getFormDataType(): int
    {
        return $this->formDataType;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Устанавливает url запроса
     * @param $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }
}
