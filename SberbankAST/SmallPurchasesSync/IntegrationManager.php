<?php

namespace App\Service\ETP\SberbankAST\SmallPurchasesSync;

use App\Entity\SmallPurchase\SberbankAST\SmallPurchase;
use App\Kernel;
use App\Message\SberbankAST\ItemDetailsProvider;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\Action\ListImporter;
use App\Service\ETP\SberbankAST\SmallPurchasesSync\Action\ItemImporter;
use App\Service\SmallPurchase\Action\SavePurchase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class IntegrationManager
 * @package App\Service\SberbankAST\SmallPurchasesSync
 * @property ListImporter $listImporter
 * @property ItemImporter $itemImporter
 * @property SavePurchase $savePurchase
 * @property MessageBusInterface $messageBus
 * @property Kernel $kernel
 *
 * @author Poltarokov SP
 * @date 07.08.2020
 */
class IntegrationManager
{
    const PURCHASES_SOURCE_POST_RUSSIA = 'POST_OF_RUSSIA'; //не работает, левый формат
    const PURCHASES_SOURCE_POST_RUSSIA_MISP = 'POST_OF_RUSSIA_MISP'; //работает
    const PURCHASES_SOURCE_TRANSNEFT_RUSSIA = 'TRANSNEFT_RUSSIA';
    const PURCHASES_SOURCE_AFK_SYSTEM = 'AFK_SYSTEM';
    const PURCHASES_SOURCE_RZD_STROI = 'RZD_STROI';
    const PURCHASES_SOURCE_ROSATOM = 'ROSATOM'; //гут
    const PURCHASES_SOURCE_CBRF = 'CBRF'; //dateEndPrequalification не проходит
    const PURCHASES_SOURCE_SB = 'SB'; //гут
    const PURCHASES_SOURCE_SB_MISP = 'SB_MISP'; //гут
    const PURCHASES_SOURCE_EN_PLUS = 'EN_PLUS'; //??? есть случаи нераспознавания
    const PURCHASES_SOURCE_RZD_TD = 'RZD_TD'; //не фильтрует до 500000
    const PURCHASES_SOURCE_DOM_RF = 'DOM_RF';//гут, повторяются детальные закупки
    const PURCHASES_SOURCE_AEROFLOT = 'AEROFLOT';//good
    const PURCHASES_SOURCE_TPLUS = 'TPLUS';//good
    const PURCHASES_SOURCE_ALROSA = 'ALROSA';//good
    const PURCHASES_SOURCE_AOOSK = 'AOOSK';//good
    const PURCHASES_SOURCE_VEBANK = 'VEBANK';//good
    //TODO помимо нераспознаваний проверить правильность ссылок на доки
    const ASYNC_PURCHASE_DETAIL_MODE = false;
    const PARSE_PORTION_LIMIT = 20;

    const SOURCE_TYPE_SETTINGS = [
        //Почта России
        self::PURCHASES_SOURCE_POST_RUSSIA_MISP => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/RussianPost/SearchQuery/PurchaseListSMiSP',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/RussianPost/File/DownloadFile?fid=',
        ],
        //
        self::PURCHASES_SOURCE_TRANSNEFT_RUSSIA => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Transneft/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/RussianPost/File/DownloadFile?fid=',
        ],
        self::PURCHASES_SOURCE_RZD_STROI => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Main/SearchQuery/UnitedPurchaseListRZDStroy',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/UnitedPurchaseListRZDStroy/File/DownloadFile?fid=',
            'filter' => [
                '%min_public_date' => '01.01.2018 00:00',
            ],
        ],
        //
        self::PURCHASES_SOURCE_ROSATOM => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Rosatom/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/Rosatom/File/DownloadFile?fid=',
        ],
        //
        self::PURCHASES_SOURCE_CBRF => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/CBRF/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/CBRF/File/DownloadFile?fid=',
            'prepareReplace' => [
                'PurchaseView' => 'Purchase',
            ],
        ],
        //
        self::PURCHASES_SOURCE_SB_MISP => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/SB/SearchQuery/PurchaseListSMiSP',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
        ],
        //
        self::PURCHASES_SOURCE_SB => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/SB/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
        ],
        //
        self::PURCHASES_SOURCE_DOM_RF => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Main/SearchQuery/UnitedPurchaseListTradeDomRF',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
        ],
        //
        self::PURCHASES_SOURCE_AEROFLOT => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Trade/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
            'filter' => [
                '%min_public_date' => '01.05.2020 00:00',
                'orgId' => 330,
            ],
        ],
        self::PURCHASES_SOURCE_TPLUS => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Trade/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
            'filter' => [
                '%min_public_date' => '01.08.2020 00:00',
                'orgId' => 334,
            ],
        ],
        self::PURCHASES_SOURCE_ALROSA => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Trade/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
            'filter' => [
                '%min_public_date' => '01.08.2020 00:00',
                'orgId' => 337,
            ],
        ],
        self::PURCHASES_SOURCE_AOOSK => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Trade/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
            'filter' => [
                '%min_public_date' => '01.08.2020 00:00',
                'orgId' => 340,
            ],
        ],
        self::PURCHASES_SOURCE_VEBANK => [
            'listRequestUrl' => 'https://utp.sberbank-ast.ru/Trade/SearchQuery/PurchaseList',
            'docDownloadBaseAddress' => 'https://utp.sberbank-ast.ru/SB/File/DownloadFile?fid=',
            'filter' => [
                '%min_public_date' => '01.05.2020 00:00',
                'orgId' => 311,
            ],
        ],
    ];

    private $listImporter;
    private $itemImporter;
    private $savePurchase;
    private $messageBus;
    private $kernel;

    private $input;
    private $output;

    public function __construct(
        ListImporter $listImporter,
        ItemImporter $itemImporter,
        SavePurchase $savePurchase,
        MessageBusInterface $messageBus,
        Kernel $kernel
    ) {
        $this->listImporter = $listImporter;
        $this->itemImporter = $itemImporter;
        $this->savePurchase = $savePurchase;
        $this->messageBus = $messageBus;
        $this->kernel = $kernel;
    }

    /**
     * Выполняет цикл перебора секций для импорта данных из них
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function runImport(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        foreach (self::SOURCE_TYPE_SETTINGS as $srcKey => $srcItem) {
            $this->runImportForSection($srcKey);
        }
    }

    /**
     * Выполняет основной цикл импорта данных для секции
     * @param string $subSource
     */
    private function runImportForSection(string $subSource): void
    {
        $offset = 0;
        $portionCount = 1;

        $this->output->writeln('Import from: ' . $subSource);

        do {
            $this->output->writeln('Portion #' . $portionCount);
            $listImportResult = $this->listImporter->getPurchasesList(
                [
                    'importSubSource' => $subSource,
                    'filter' => array_merge([
                        '%purchase_max_amount' => 500000,
                        '%offset' => $offset,
                        '%limit' => self::PARSE_PORTION_LIMIT,
                        '%min_public_date' => '01.08.2020 00:00'
                    ], self::SOURCE_TYPE_SETTINGS[$subSource]['filter'] ?? []),
                ],
                $this->output
            );

            $this->itemImporter->setImportParams([
                'importSubSource' => $subSource
            ]);

            $purchaseEntities = $listImportResult['purchaseEntities'];
            $this->runPortionImport($purchaseEntities);

            //var_export($purchaseEntities);

            $offset += self::PARSE_PORTION_LIMIT;
            $portionCount++;
        } while (count($purchaseEntities) > 0);
    }

    /**
     * Выполняет импорт данных для порции закупок
     * @param SmallPurchase[] $purchaseEntities
     */
    private function runPortionImport(array $purchaseEntities): void
    {
        foreach ($purchaseEntities as $purchaseEntityItem) {
            //Ожидание чтобы не нас забанили как ддос-атаку
            usleep(500000);

            if (self::ASYNC_PURCHASE_DETAIL_MODE) {
                $itemDetailsProvider = new ItemDetailsProvider(
                    $purchaseEntityItem,
                    $this->itemImporter,
                    $this->kernel,
                    $this->output
                );
                $this->messageBus->dispatch($itemDetailsProvider);
            } else {
                //вариант без асинхронного выполнения (предыдущие 3 строки)
                $resultPurchase = $itemImportResult = $this->itemImporter->getPurchaseItem(
                    $purchaseEntityItem,
                    $this->output
                );
                if ($resultPurchase) {
                    $this->savePurchase->execute($resultPurchase);
                } else {
                    $this->output->writeln('Unsuitable purchase lot or unknown purchase structure: url = ' .
                        $purchaseEntityItem->getUrlToShowcase());
                }
            }
        }
    }
}
