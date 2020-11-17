<?php

namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Request;

use App\Service\ETP\SberbankAST\SmallPurchasesSync\IntegrationManager;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces\IRequestBuilder;

/**
 * Class PurchasesListRequest
 * @package App\Service\ETP\SberbankAST\SmallPurchasesSync\Request
 *
 * @author Poltarokov SP
 * @date 09.08.2020
 */
class PurchasesListRequest extends Request implements IRequestBuilder
{
    public function __construct()
    {
        parent::__construct();
        $this->headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'x-requested-with: XMLHttpRequest'
        );
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return 'POST';
    }

    /**
     * @inheritDoc
     * @param $params
     * [
     *  '%purchase_max_amount' => 500000,
     *  '%offset' => 0,
     *  '%limit' => 20,
     *  '%min_public_date' => '10.08.2020 00:00'
     * ]
     */
    public function produceFormData(array $params): array
    {
        $xmlData = str_replace(
            array_keys($params),
            array_values($params),
            "<elasticrequest><filters><mainSearchBar><value></value>
                <type>best_fields</type><minimum_should_match>100%</minimum_should_match>
                </mainSearchBar><purchAmount><minvalue></minvalue><maxvalue>%purchase_max_amount</maxvalue>
                </purchAmount><PublicDate><minvalue>%min_public_date</minvalue><maxvalue>
                </maxvalue></PublicDate><PurchaseStageTerm><value></value><visiblepart>
                </visiblepart></PurchaseStageTerm><CustomerCondition><value></value>
                </CustomerCondition><CustomerDictionary><value></value></CustomerDictionary>
                <customer><visiblepart></visiblepart></customer><RegionNameTerm><value></value>
                <visiblepart></visiblepart></RegionNameTerm><RequestStartDate><minvalue></minvalue>
                <maxvalue></maxvalue></RequestStartDate><RequestDate><minvalue></minvalue><maxvalue>
                </maxvalue></RequestDate><AuctionBeginDate><minvalue></minvalue><maxvalue>
                </maxvalue></AuctionBeginDate><okdp2MultiMatch><value></value></okdp2MultiMatch>
                <okdp2tree><value></value><productField></productField><branchField></branchField>
                </okdp2tree><classifier><visiblepart></visiblepart></classifier><orgCondition>
                <value></value></orgCondition><orgDictionary><value></value></orgDictionary>
                <organizator><visiblepart></visiblepart></organizator><PurchaseWayTerm><value>
                </value><visiblepart></visiblepart></PurchaseWayTerm><BranchNameTerm><value>
                </value><visiblepart></visiblepart></BranchNameTerm><IsSMPTerm><value></value>
                <visiblepart></visiblepart></IsSMPTerm><purchCurrency><value></value><visiblepart>
                </visiblepart></purchCurrency><statistic><totalProc>27 541</totalProc>
                <TotalSum>74.00 Млрд.</TotalSum><DistinctOrgs>91</DistinctOrgs></statistic>
                </filters><fields><field>TradeSectionId</field><field>purchAmount</field>
                <field>purchCurrency</field><field>purchCodeTerm</field><field>purchCode</field>
                <field>PurchaseTypeName</field><field>purchStateName</field><field>OrgName</field>
                <field>SourceTerm</field><field>PublicDate</field><field>RequestDate</field>
                <field>RequestStartDate</field><field>RequestAcceptDate</field>
                <field>AuctionBeginDate</field><field>auctResultDate</field>
                <field>CreateRequestHrefTerm</field><field>CreateRequestAlowed</field>
                <field>purchName</field><field>SourceHrefTerm</field><field>objectHrefTerm</field>
                <field>needPayment</field><field>IsSMP</field><field>isIncrease</field>
                <field>IntegratorCode</field><field>IntegratorCodeTerm</field>
                <field>PurchaseExplanationRequestHrefTerm</field><field>PurchaseId</field>
                <field>PurchaseTypeType</field></fields><sort><value>default</value><direction>
                </direction></sort><aggregations><empty><filterType>filter_aggregation</filterType>
                <field></field><min_doc_count>0</min_doc_count><order>asc</order></empty>
                </aggregations><size>%limit</size><from>%offset</from></elasticrequest>"
        );

        $formData = [
            'xmlData' => $xmlData,
            'orgId' => 0,
            'buId' => 0,
            'personId' => 0,
            'buMainId' => 0,
            'personMainId' => 0
        ];

        foreach ($formData as $fdKey => $fdVal) {
            if (array_key_exists($fdKey, $params)) {
                $formData[$fdKey] = $params[$fdKey];
            }
        }

        $this->filter->addFormData($formData);

        return $this->filter->getFormData();
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
        return IntegrationManager::
        SOURCE_TYPE_SETTINGS[$this->importSubSource]['listRequestUrl'];
    }

    /**
     * @inheritDoc
     */
    public function buildUrl(): void
    {
    }
}
