<?php

namespace app\modules\tickets\repositories;

use app\modules\tickets\enum\AgencyAdNetworkTypeEnum;
use app\modules\tickets\enum\TicketAccountTypeEnum;
use app\modules\tickets\enum\TicketDimensionEnum;
use app\modules\tickets\models\cabinet\ExternalTicket;
use app\modules\tickets\models\cabinet\ExternalTicketInfo;
use app\modules\tickets\repositories\contracts\ExternalCabinetTicketRepositoryInterface;
use app\modules\tickets\services\EnvironmentService;
use DateTimeImmutable;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

abstract class AbstractDBExternalCabinetTicketRepository implements ExternalCabinetTicketRepositoryInterface
{
    /** @var Connection */
    protected $dbConnection;

    /** @var EnvironmentService */
    protected $environmentService;

    public function __construct(EnvironmentService $environmentService)
    {
        $this->dbConnection = $this->getDbConnection();
        $this->environmentService = $environmentService;
    }

    protected abstract function getDbConnection(): Connection;

    /**
     * @param DateTimeImmutable $dateFrom
     * @return ExternalTicket[]
     */
    public function findFromDate(DateTimeImmutable $dateFrom): array
    {
        $condition = 't.created > :dateFrom OR t.modified > :dateFrom';
        $params = [':dateFrom' => $dateFrom->format('Y-m-d H:i:s')];

        $arr = $this->baseRequestOnDb($condition, $params);

        return $arr;
    }

    public function updateStatus(int $id, string $status)
    {
        $this->dbConnection->createCommand()->update(
            'tickets',
            ['status' => $status],
            ['id' => $id]
        )->execute();
    }

    public function getById(int $id): ExternalTicket
    {
        $condition = 't.id = :id';
        $params = [':id' => $id];

        $arr = $this->baseRequestOnDb($condition, $params);

        return $arr[0];
    }

    public function setDateTimeModified(int $id, string $dateTime)
    {
        $this->dbConnection->createCommand()->update(
            'tickets',
            ['modified' => $dateTime],
            ['id' => $id]
        )->execute();
    }

    public function setPlanfixTaskId(int $id, int $planfixTaskId)
    {
        $this->dbConnection->createCommand()->update(
            'tickets',
            ['planfix_task_id' => $planfixTaskId],
            ['id' => $id]
        )->execute();
    }

    private function baseRequestOnDb(string $condition, array $params)
    {
        $query = '
            SELECT
                t.*,
                tc.name as category_name,
                tcs.name as category_sub_name,
                af.company as affiliate_company,
                af.employee_id as affiliate_employee_id,
                ad.company as advertise_company
            FROM 
                tickets t
            LEFT JOIN
                tickets_categories tc ON t.ticket_category_id = tc.id
            LEFT JOIN
                tickets_categories_sub tcs ON t.ticket_category_sub_id = tcs.id
            LEFT JOIN
                affiliates af ON (af.id = t.account_id AND t.account_type = :typeAffiliate)
            LEFT JOIN
                advertisers ad ON (ad.id = t.account_id AND t.account_type = :typeAdvertise)
            WHERE ' . $condition;

        $requestDb = $this->dbConnection
            ->createCommand($query);

        foreach ($params as $name => $value) {
            $requestDb->bindValue($name, $value);
        }

        $requestDb->bindValue(':typeAffiliate', TicketAccountTypeEnum::TYPE_AFFILIATE)
            ->bindValue(':typeAdvertise', TicketAccountTypeEnum::TYPE_ADVERTISE);

        $allTickets = $requestDb->queryAll();
        if (!count($allTickets)) {
            return [];
        }

        $ticketIds = array_column($allTickets, 'id');
        $ticketsAdditionals = $this->getTicketsAdditionalInfo($ticketIds);


        $arr = [];

        foreach ($allTickets as $ticket) {
            $accountCompany = $ticket['account_type'] === TicketAccountTypeEnum::TYPE_AFFILIATE ?
                $ticket['affiliate_company'] : $ticket['advertise_company'];

            $arr[] = new ExternalTicket([
                'id' => $ticket['id'],
                'employeeId' => $ticket['affiliate_employee_id'],
                'planfixTaskId' => $ticket['planfix_task_id'],
                'project' => $this->environmentService->getProjectEnvironment(),
                'category' => $ticket['ticket_category_id'],
                'subcategory' => $ticket['ticket_category_sub_id'],
                'title' => $ticket['title'],
                'description' => $ticket['description'],
                'accountId' => $ticket['account_id'],
                'accountType' => $ticket['account_type'],
                'accountCompany' => $accountCompany,
                'status' => $ticket['status'],
                'created' => $ticket['created'],
                'modified' => $ticket['modified'],
                'additionalInformation' => $ticketsAdditionals[$ticket['id']] ?? null,
                'managerId' => $ticket['manager_id'],
            ]);
        }

        return $arr;
    }

    private function getTicketsAdditionalInfo(array $ticketIds)
    {
        $ticketAdditionalWihIndex = [];

        $ticketsDimensionsGroupTicketId = $this->getDimensionsByTicketIdsGroupTicketId($ticketIds);

        $cabinetIds = $this->getCabinetsIdsFromTicketDimensions($ticketsDimensionsGroupTicketId);
        $agencyCabinetsWithIndex = count($cabinetIds) ? $this->getAgencyCabinetsByIds($cabinetIds) : [];
        $countriesWithIndex = $this->getCountriesIndexById();

        foreach ($ticketsDimensionsGroupTicketId as $ticketId => $ticketDimensions) {
            $ticketAdditionalWihIndex[$ticketId] = ExternalTicketInfo::getInstanceFromArrayDimensions(
                $ticketDimensions,
                $agencyCabinetsWithIndex,
                $countriesWithIndex
            );
        }

        return $ticketAdditionalWihIndex;
    }

    /**
     * @param array[] $ticketsDimensions
     * @return int[]
     */
    private function getCabinetsIdsFromTicketDimensions(array $ticketsDimensions): array
    {
        $codeAgencyCabinets = [
            TicketDimensionEnum::DIMENSION_AGENCY_CABINET_ID,
            TicketDimensionEnum::DIMENSION_SOURCE_AGENCY_CABINET_ID,
            TicketDimensionEnum::DIMENSION_DST_AGENCY_CABINET_ID,
        ];

        $cabinetIds = [];

        foreach ($ticketsDimensions as $ticketId => $ticketDimensions) {
            foreach ($ticketDimensions as $ticketDimension) {
                if (!in_array($ticketDimension['code'], $codeAgencyCabinets)) {
                    continue;
                }

                $cabinetIds[] = $ticketDimension['value'];
            }
        }

        return $cabinetIds;
    }

    /**
     * @param int[] $ticketIds
     * @return array[]
     */
    private function getDimensionsByTicketIdsGroupTicketId(array $ticketIds): array
    {
        $queryDimensions = '
            SELECT
                tdv.*,
                td.name as dimension_name
            FROM
                tickets_dimension_value tdv
            LEFT JOIN
                tickets_dimension td ON tdv.ticket_dimension_id = td.id
            WHERE
                tdv.ticket_id IN (' . implode(', ', $ticketIds) . ')';

        $ticketDimensions = $this->dbConnection
            ->createCommand($queryDimensions)
            ->queryAll();

        $dimensionsByTicketId = [];

        foreach ($ticketDimensions as $ticketDimension) {
            $ticketId = $ticketDimension['ticket_id'];

            if (!array_key_exists($ticketId, $dimensionsByTicketId)) {
                $dimensionsByTicketId[$ticketId] = [];
            }

            $dimensionsByTicketId[$ticketId][] = $ticketDimension;
        }

        return $dimensionsByTicketId;
    }

    /**
     * @param int[] $ids
     * @return array[]
     */
    private function getAgencyCabinetsByIds(array $ids): array
    {
        $query = '
            SELECT
                ac.*,
                aan.title as network_title,
                aan.type as network_type
            FROM
                agency_cabinet ac
            LEFT JOIN 
                agency_ad_network aan ON ac.ad_network_id = aan.id
            WHERE
                ac.id IN (' . implode(', ', $ids) . ')';

        $agencyCabinets = $this->dbConnection
            ->createCommand($query)
            ->queryAll();

        $agencyCabinetByTypeIds = [];

        foreach ($agencyCabinets as $agencyCabinet) {
            if (!array_key_exists($agencyCabinet['network_type'], $agencyCabinetByTypeIds)) {
                $agencyCabinetByTypeIds[$agencyCabinet['network_type']] = [];
            }

            $agencyCabinetByTypeIds[$agencyCabinet['network_type']][] = $agencyCabinet['id'];
        }

        $agencyCabinetsWithIndex = ArrayHelper::index($agencyCabinets, 'id');

        foreach ($agencyCabinetByTypeIds as $type => $cabinetIds) {
            $infoAgencyCabinets = $this->getAgencyByTypeAndIds($type, $cabinetIds);

            foreach ($infoAgencyCabinets as $infoAgencyCabinet) {
                $agencyCabinetsWithIndex[$infoAgencyCabinet['id']]['info'] = $infoAgencyCabinet;
            }
        }

        return $agencyCabinetsWithIndex;
    }

    /**
     * @param string $type
     * @param int[] $ids
     * @return array[]
     */
    private function getAgencyByTypeAndIds(string $type, array $ids)
    {
        $tableMap = [
            AgencyAdNetworkTypeEnum::TYPE_YANDEX_DIRECT => 'agency_cabinet_yandex',
            AgencyAdNetworkTypeEnum::TYPE_GOOGLE => 'agency_cabinet_google',
            AgencyAdNetworkTypeEnum::TYPE_VK => 'agency_cabinet_vk',
            AgencyAdNetworkTypeEnum::TYPE_MY_TARGET => 'agency_cabinet_my_target',
        ];

        $query = 'SELECT * FROM ' . $tableMap[$type] . ' WHERE id IN (' . implode(', ', $ids) . ')';
        $cabinets = $this->dbConnection->createCommand($query)->queryAll();

        return $cabinets;
    }

    /**
     * @return array[]
     */
    private function getCountriesIndexById(): array
    {
        $countries = $this->dbConnection->createCommand('SELECT * FROM geo_countries')->queryAll();
        $countries = ArrayHelper::index($countries, 'id');

        return $countries;
    }
}
