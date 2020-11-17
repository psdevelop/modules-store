<?php


namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Request;

use App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces\IRequestBuilder;

/**
 * Class PurchaseItemRequest
 * @package App\Service\ETP\SberbankAST\SmallPurchasesSync\Request
 *
 * @author Poltarokov SP
 * 07.08.2020
 */
class PurchaseItemRequest extends Request implements IRequestBuilder
{
    public function __construct()
    {
        parent::__construct();
        $this->headers = [
            'Content-Type: text/html,application/xhtml+xml,application/xml;'
            . 'q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return 'GET';
    }

    /**
     * @inheritDoc
     */
    public function produceFormData(array $params): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getPostFields(): array
    {
        return $this->filter->getFormData();
    }

    /**
     * @inheritDoc
     */
    public function setImportSubSource(string $source): void
    {
        $this->importSubSource = $source;
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        $this->buildUrl();
        return $this->url;
    }

    /**
     * @inheritDoc
     */
    public function buildUrl(): void
    {
    }
}
