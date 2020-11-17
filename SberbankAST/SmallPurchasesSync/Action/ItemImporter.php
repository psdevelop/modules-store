<?php


namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Action;

use App\Entity\Elasticsearch\LotItem;
use App\Entity\SmallPurchase\SberbankAST\Document;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\IntegrationManager;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\ParserClient;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\Request\PurchaseItemRequest;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\SmallPurchase\SberbankAST\SmallPurchase;
use Symfony\Component\DomCrawler\Crawler;
use App\Service\Utils\Action\GetPreparedSerializer;
use App\Purchase\Sources;

/**
 * Class ItemImporter
 * @package App\Service\SberbankAST\SmallPurchasesSync\Action
 * @property ParserClient $parserClient
 * @property PurchaseItemRequest $request
 * @property GetPreparedSerializer $preparedSerializer;
 *
 * @author Poltarokov SP
 * 06.08.2020
 */
class ItemImporter
{

    const PURCHASE_COMPOSE_FILTER_FULL = [
        [
            'filter' => 'Purchase PurchaseInfoTotal PurchaseInfo PurchaseTypeInfo',
            'replaceData' => [
                [
                    'search' => 'PurchaseTypeName',
                    'replace' => 'PurchaseInfoTypeName'
                ],
            ],
        ],
        'Purchase PurchaseInfoTotal PurchasePlan ExaminationInfo',
        'Purchase PurchaseInfoTotal ContactInfo',
        'Purchase PurchaseInfoTotal OrganizatorInfo',
        'Purchase PurchaseInfoTotal PurchaseInfo',
        'Purchase PurchaseInfoTotal SupplierRequirementInfo',
        'Purchase PurchasePlan ApplSubmissionInfo',
        'Purchase PurchasePlan SummingupInfo',
        'Purchase PurchasePlan ConsiderationFirstPartInfo',
        'Purchase PurchasePlan ConsiderationSecondPartInfo',
        'Purchase PurchasePlan PricesProvisionInfo',
        'Purchase > OrganizatorInfo',
        'Purchase > PurchaseInfo',
        'Purchase > PurchasePlan',
        'Purchase > PurchaseInfoDocumentation',
        'Purchase > TenderInfo',
        'Purchase > PurchaseTypeInfo',
    ];

    /*const PURCHASE_COMPOSE_FILTER2 = [

    ];

    const PURCHASE_COMPOSE_FILTER_FULL =
        self::PURCHASE_COMPOSE_FILTER1 +
        self::PURCHASE_COMPOSE_FILTER2;*/

    const PURCHASE_COMPOSE_PARAMS = [
        IntegrationManager::PURCHASES_SOURCE_POST_RUSSIA =>
            self::PURCHASE_COMPOSE_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_TRANSNEFT_RUSSIA =>
            self::PURCHASE_COMPOSE_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_ROSATOM =>
            self::PURCHASE_COMPOSE_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_CBRF =>
            self::PURCHASE_COMPOSE_FILTER_FULL,
    ];

    const LOTS_ENUMERATION_FILTER1 = 'Purchase Bids Bid BidInfoTotal';
    const LOTS_ENUMERATION_FILTER2 = 'Purchase Bids Bid';
    const LOTS_ENUMERATION_FILTER_FULL = [
        //self::LOTS_ENUMERATION_FILTER1,
        self::LOTS_ENUMERATION_FILTER2
    ];

    const LOT_ITEM_TAGS = [
        IntegrationManager::PURCHASES_SOURCE_POST_RUSSIA => self::LOTS_ENUMERATION_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_TRANSNEFT_RUSSIA => self::LOTS_ENUMERATION_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_ROSATOM => self::LOTS_ENUMERATION_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_CBRF => self::LOTS_ENUMERATION_FILTER_FULL,
    ];

    const LOT_EXCLUDE_PARAMS = [
        [
            'filter' => 'BidCustomerDataInfo PlanInfo BidEISExcludePurchaseFromPlan',
            'value' => '1'
        ],
        [
            'filter' => 'bidcustomerdatainfo planinfo bideisexcludepurchasefromplan',
            'value' => '1'
        ],
    ];

    const LOT_POSITION_TAGS = [
        'BidCustomerDataInfo BidPositionsDiv',
        'BidInfo Positions',
        'Bid Positions',
    ];

    const LOT_COMPOSE_FILTER_FULL = [
        'BidInfoTotal',
        'ApplicationSupplyInfo', //
        'BidInfo', //
        'BidPlaceInfo', //
        'BidPriceInfo', //
        'BidCustomers',
        'BidCustomerDatas',
        'BidCustomerDataInfo BidCustomerInfo', //
        'BidCustomerDataInfo BidCustomerPriceInfo', //
        'BidCustomerDataInfo PlanInfo', //
        //'BidInfo',
        'BidInfo BidAdditionalInfo',
    ];

    const LOT_COMPOSE_PARAMS = [
        IntegrationManager::PURCHASES_SOURCE_POST_RUSSIA => self::LOT_COMPOSE_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_TRANSNEFT_RUSSIA => self::LOT_COMPOSE_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_ROSATOM => self::LOT_COMPOSE_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_CBRF => self::LOT_COMPOSE_FILTER_FULL,
    ];

    const DOCS_ENUMERATION_FILTER1 = 'Purchase PurchaseDocumentationDocsInfo Docs file';
    const DOCS_ENUMERATION_FILTER2 = 'Purchase Docs AuctionDocs file';
    const DOCS_ENUMERATION_FILTER3 = 'Purchase PurchaseDocumentationDocsInfo DocFiles document';
    const DOCS_ENUMERATION_FILTER4 = 'Purchase DocsDiv Docs file';
    const DOCS_ENUMERATION_FILTER_FULL = [
        self::DOCS_ENUMERATION_FILTER1,
        self::DOCS_ENUMERATION_FILTER2,
        self::DOCS_ENUMERATION_FILTER3,
        self::DOCS_ENUMERATION_FILTER4,
    ];

    const DOCUMENTS_ITEM_TAGS = [
        IntegrationManager::PURCHASES_SOURCE_POST_RUSSIA => self::DOCS_ENUMERATION_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_TRANSNEFT_RUSSIA => self::DOCS_ENUMERATION_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_ROSATOM => self::DOCS_ENUMERATION_FILTER_FULL,
        IntegrationManager::PURCHASES_SOURCE_CBRF => self::DOCS_ENUMERATION_FILTER_FULL,
    ];

    const DOCUMENT_COMPOSE_PARAMS = [
        IntegrationManager::PURCHASES_SOURCE_POST_RUSSIA =>
            [

            ],
        IntegrationManager::PURCHASES_SOURCE_TRANSNEFT_RUSSIA =>
            [

            ],
        IntegrationManager::PURCHASES_SOURCE_ROSATOM =>
            [

            ],
        IntegrationManager::PURCHASES_SOURCE_CBRF =>
            [

            ],
    ];

    private $parserClient;
    private $request;
    private $preparedSerializer;

    /** @var Crawler $crawler */
    private $crawler;
    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    private $serializer;
    /** @var string */
    private $importSubSource;

    public function __construct(
        ParserClient $parserClient,
        PurchaseItemRequest $request,
        GetPreparedSerializer $preparedSerializer
    ) {
        $this->parserClient = $parserClient;
        $this->request = $request;
        $this->preparedSerializer = $preparedSerializer;

        $this->serializer = $this->preparedSerializer->getSberbankASTSerializer();
    }

    /**
     * Устанавливает параметры парсинга импортируемых данных
     * @param array $params
     */
    public function setImportParams(array $params): void
    {
        if (isset($params['importSubSource']) && $params['importSubSource']) {
            $this->importSubSource = $params['importSubSource'];
            $this->request->setImportSubSource($params['importSubSource']);
        }
    }

    /**
     * Возвращает объект SmallPurchase, наполненный данными
     * со страницы детальной информации о закупке
     * @param SmallPurchase $purchase
     * @param OutputInterface $output
     * @return SmallPurchase|null
     */
    public function getPurchaseItem(SmallPurchase $purchase, OutputInterface $output)
    {
        $this->request->setUrl($purchase->getUrlToShowcase());
        $htmlResponse = $this->parserClient->sendRequest($this->request);

        $htmlResponse['errno'] && $output->writeln(var_export($htmlResponse['errno'], true));
        $htmlResponse['errmsg'] && $output->writeln(var_export($htmlResponse['errmsg'], true));

        $htmlResponse = $htmlResponse['content'];
        $this->crawler = new Crawler($htmlResponse);

        $xmlContainer = $this->crawler->filter('form#docForm input#xmlData')->first();
        if (!$xmlContainer->count()) {
            $output->writeln('Not found XML data! ' . $purchase->getUrlToShowcase());
            return null;
        }
        $itemXML = $xmlContainer ? $xmlContainer->attr('value') : '';

        $prepareReplace = IntegrationManager::SOURCE_TYPE_SETTINGS[$this->importSubSource]['prepareReplace'] ?? [];
        foreach ($prepareReplace as $pKey => $pVal) {
            $itemXML = str_replace($pKey, $pVal, $itemXML);
        }

        //$output->writeln($itemXML);
        $this->crawler = new Crawler($itemXML);

        /** @var LotItem[] $lots */
        $lots = $this->getLots();
        if (!$lots) {
            //$output->writeln($itemXML);
            return null;
        }

        //$output->writeln($itemXML);
        $output->writeln($purchase->getUrlToShowcase());

        $purchaseXML = '<SmallPurchase>';
        $purchaseXML .= '<DownloadSource>' . Sources::SBERBANK_AST . '</DownloadSource>';
        $purchaseXML .= '<SubSource>' . $this->importSubSource . '</SubSource>';

        $purchaseXML .= self::composeTargetTagsFromXML(
            $this->crawler,
            self::PURCHASE_COMPOSE_PARAMS[$this->importSubSource]
            ?? self::PURCHASE_COMPOSE_FILTER_FULL
        );

        $purchaseXML .= '</SmallPurchase>';

        $this->serializer->deserialize(
            $purchaseXML,
            SmallPurchase::class,
            'xml',
            array('object_to_populate' => $purchase)
        );

        $purchase->setLotItems($lots);
        $purchase->setDocuments($this->getDocuments());

        return $purchase;
    }

    /**
     * Формирует и возвращает массив объектов-лотов
     * для закупки, исходя из парсинга ее детальной информации
     * @return LotItem[]|null
     */
    private function getLots()
    {
        $excludePurchaseFromPlan = true;
        $serializer = $this->serializer;
        $importSubsource = $this->importSubSource;
        $matchedFilter = null;

        $filterList = self::LOT_ITEM_TAGS[$this->importSubSource]
            ?? self::LOTS_ENUMERATION_FILTER_FULL;
        foreach ($filterList as $filterItem) {
            if ($this->crawler->filter($filterItem)->count()) {
                $matchedFilter = $filterItem;
            }
        }

        if (!$matchedFilter) {
            return null;
        }
        $lots = $this->crawler->filter($matchedFilter)->each(function (Crawler $node, $i)
 use (&$excludePurchaseFromPlan, $serializer, $importSubsource) {
            $lotXML = '<LotItem>';

            $lotComposeParams = self::LOT_COMPOSE_PARAMS[$importSubsource]
                ?? self::LOT_COMPOSE_FILTER_FULL;
            if ($lotComposeParams) {
                $lotXML .= self::composeTargetTagsFromXML(
                    $node,
                    $lotComposeParams
                );
            } else {
                $lotXML .= $node->html();
            }

            foreach (self::LOT_EXCLUDE_PARAMS as $excludeParamsItem) {
                if (is_array($excludeParamsItem) && ($excludeParamsItem['filter'] ?? false)) {
                    $excludeLot = $node->filter($excludeParamsItem['filter'])->first();
                    if ($excludeLot->count() && $excludeLot->text() !==
                        $excludeParamsItem['value']) {
                        $excludePurchaseFromPlan = false;
                        return null;
                    }
                }
            }

            $lotPositionTag = false;
            foreach (self::LOT_POSITION_TAGS as $lotPositionTagItem) {
                if ($node->filter($lotPositionTagItem)->count()) {
                    $lotPositionTag = $lotPositionTagItem;
                }
            }

            $lotPositionsXML = false;
            if ($lotPositionTag) {
                $lotPositionsXML = $node->filter($lotPositionTag)->first();
            }

            if ($lotPositionsXML && $lotPositionsXML->count()) {
                $lotPositionsXML = '<BidPositions>' . $lotPositionsXML->html() . '</BidPositions>';
                $lotPositions = $serializer->decode($lotPositionsXML, 'xml');
                $lotXML .= '<LotPositions>' . json_encode($lotPositions) . '</LotPositions>';
            } else {
                echo 'Not found lot positions. ';// . $node->html();
            }

            $lotXML .= '</LotItem>';

            return $serializer->deserialize(
                $lotXML,
                'App\Entity\SmallPurchase\SberbankAST\LotItem',
                'xml'
            );
        });

        if (!$excludePurchaseFromPlan) {
            echo 'ExcludePurchaseFromPlan: 223FZ.';
            return null;
        }

        return $lots;
    }

    /**
     * Формирует и возвращает массив объектов документов
     * для закупки, исходя из парсинга ее детальной информации
     * @return Document[]
     */
    private function getDocuments()
    {
        $subSource = $this->importSubSource;
        $serializer = $this->serializer;

        $matchedFilter = null;

        $filterList = self::DOCUMENTS_ITEM_TAGS[$subSource]
            ?? self::DOCS_ENUMERATION_FILTER_FULL;
        foreach ($filterList as $filterItem) {
            if ($this->crawler->filter($filterItem)->count()) {
                $matchedFilter = $filterItem;
            }
        }

        if (!$matchedFilter) {
            return null;
        }

        return $this->crawler->filter($matchedFilter)
            ->each(function (Crawler $node, $i) use ($serializer, $subSource) {

                $docXML = '<Document>';

                $documentComposeParams = self::DOCUMENT_COMPOSE_PARAMS[$subSource] ?? [];
                if ($documentComposeParams) {
                    $docXML .= self::composeTargetTagsFromXML($node, $documentComposeParams);
                } else {
                    $docXML .= $node->html();
                }

                $docXML .= "<SubSource>{$subSource}</SubSource>";
                $docXML .= '</Document>';

                return $serializer->deserialize(
                    $docXML,
                    'App\Entity\SmallPurchase\SberbankAST\Document',
                    'xml'
                );
            });
    }

    /**
     * Создает композицию из тэгов с данными на одном уровне
     * для десериализации (тэги извлекаются фильтром из разных уровней
     * исходного XML), при необходимости производит замены
     * имен тэгов, возвращает целевую последовательность тэгов в виде
     * XML-строки
     * @param Crawler $crawler
     * @param array $composeParams
     * @return string
     */
    private static function composeTargetTagsFromXML(Crawler $crawler, array $composeParams)
    {
        $mapParseResult = array_map(function ($tagName) use ($crawler) {
            $replaceData = [];
            if (is_array($tagName)) {
                $replaceData = $tagName['replaceData'] ?? [];
                $tagName = $tagName['filter'];
            }
            $node = $crawler->filter($tagName)->first();
            if ($node->count()) {
                $nodeHTML = $node->html();

                foreach ($replaceData as $replaceItem) {
                    if (isset($replaceItem['search']) && $replaceItem['search'] &&
                        isset($replaceItem['replace']) && $replaceItem['replace']) {
                        $nodeHTML = str_replace(
                            $replaceItem['search'],
                            $replaceItem['replace'],
                            $nodeHTML
                        );
                    }
                }

                return $nodeHTML;
            }

            return '';
        }, $composeParams);

        return implode($mapParseResult);
    }
}
