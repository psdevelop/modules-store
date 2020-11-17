<?php

namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Action;

use App\Service\ETP\SberbankAST\SmallPurchasesSync\ParserClient;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\Request\PurchasesListRequest;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use App\Service\Utils\Action\GetPreparedSerializer;

/**
 * Class ListImporter
 * @package App\Service\SberbankAST\SmallPurchasesSync\Action
 * @property ParserClient $sberASTParserClient
 * @property PurchasesListRequest $request
 * @property GetPreparedSerializer $preparedSerializer;
 *
 * @author Poltarokov SP
 * @date 07.08.2020
 */
class ListImporter
{

    private $parserClient;
    private $request;
    private $preparedSerializer;

    public function __construct(
        ParserClient $parserClient,
        PurchasesListRequest $request,
        GetPreparedSerializer $preparedSerializer
    ) {
        $this->parserClient = $parserClient;
        $this->request = $request;
        $this->preparedSerializer = $preparedSerializer;
    }

    public function getPurchasesList(array $params, OutputInterface $output): array
    {

        $this->request->setImportSubSource($params['importSubSource']);
        $this->request->produceFormData($params['filter']);
        $response = $this->parserClient->sendRequest($this->request);

        $response['errno'] && $output->writeln(var_export($response['errno'], true));
        $response['errmsg'] && $output->writeln(var_export($response['errmsg'], true));

        $resultJson = $response['content'];

        $xmlStartTerm = '\u003cdatarow'; //xml start terminator
        $xmlEndTerm = '\u003c/datarow\u003e'; //xml end terminator

        $xmlStart = strpos($response['content'], $xmlStartTerm);
        $xmlEnd = strpos($response['content'], $xmlEndTerm) + strlen($xmlEndTerm);
        $resultXML = substr($resultJson, $xmlStart, $xmlEnd - $xmlStart);
        $resultXML = json_decode(sprintf('"%s"', $resultXML));
        $crawler = new Crawler($resultXML);

        $serializer = $this->preparedSerializer->getSberbankASTSerializer();
        $purchaseEntities = $crawler->filter('_source')->each(function (Crawler $node, $i) use ($serializer) {
            $xmlEntityItem = '<SmallPurchase>' . $node->html() . '</SmallPurchase>';
            return $serializer->deserialize(
                $xmlEntityItem,
                'App\Entity\SmallPurchase\SberbankAST\SmallPurchase',
                'xml'
            );
        });

        $totalCountContainer = $crawler->filter('datarow > total > value')->first();
        $totalCount = $totalCountContainer->count() > 0
            ? $totalCountContainer->text()
            : false;
        if ($totalCount) {
            $output->writeln('totalCount: ' . $totalCount);
        }

        return [
            'purchaseEntities' => $purchaseEntities,
            'totalCount' => $totalCount
        ];
    }
}
