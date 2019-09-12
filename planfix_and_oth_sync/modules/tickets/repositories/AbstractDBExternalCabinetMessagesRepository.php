<?php

namespace app\modules\tickets\repositories;

use app\modules\tickets\enum\TicketAccountTypeEnum;
use app\modules\tickets\models\cabinet\ExternalTicketMessage;
use app\modules\tickets\repositories\contracts\ExternalCabinetMessagesRepositoryInterface;
use DateTimeImmutable;
use Yii;
use yii\db\Connection;

abstract class AbstractDBExternalCabinetMessagesRepository implements ExternalCabinetMessagesRepositoryInterface
{
    /** @var Connection  */
    protected $dbConnection;

    public function __construct()
    {
        $this->dbConnection = $this->getDbConnection();
    }

    protected abstract function getDbConnection(): Connection;

    /**
     * @param DateTimeImmutable $dateFrom
     * @return ExternalTicketMessage[]
     */
    public function findFromDate(DateTimeImmutable $dateFrom): array
    {
        $query = '
            SELECT
              *
            FROM 
                tickets_messages
            WHERE
                account_type != :accountType
            AND 
                (created > :dateFrom OR modified > :dateFrom)';

        $allMessages = $this->dbConnection
            ->createCommand($query)
            ->bindValue(':dateFrom', $dateFrom->format('Y-m-d H:i:s'))
            ->bindValue(':accountType', TicketAccountTypeEnum::TYPE_MANAGER)
            ->queryAll();


        if (! count($allMessages)) {
            return [];
        }

        $arr = [];

        foreach ($allMessages as $ticket) {
            $arr[] = new ExternalTicketMessage([
                'id' => $ticket['id'],
                'accountId' => $ticket['account_id'],
                'accountType' => $ticket['account_type'],
                'message' => $ticket['message'],
                'ticketId' => $ticket['ticket_id'],
                'created' => $ticket['created'],
                'modified' => $ticket['modified'],
            ]);
        }

        return $arr;
    }

    public function getById(int $id): ExternalTicketMessage
    {
        $query = '
            SELECT
              *
            FROM 
                tickets_messages
            WHERE
                id = :id';

        $message = $this->dbConnection
            ->createCommand($query)
            ->bindValue(':id', $id)
            ->queryOne();


        return new ExternalTicketMessage([
            'id' => $message['id'],
            'accountId' => $message['account_id'],
            'accountType' => $message['account_type'],
            'message' => $message['message'],
            'ticketId' => $message['ticket_id'],
            'created' => $message['created'],
            'modified' => $message['modified'],
        ]);
    }

    public function save(ExternalTicketMessage $externalTicketMessage)
    {
        $this->dbConnection->createCommand()
            ->insert(
                'tickets_messages',
                [
                    'account_id' => $externalTicketMessage->accountId,
                    'account_type' => $externalTicketMessage->accountType,
                    'message' => $externalTicketMessage->message,
                    'ticket_id' => $externalTicketMessage->ticketId,
                ]
            )
            ->execute();

        $externalTicketMessage->id = $this->dbConnection->getLastInsertID();
    }

    public function deleteById(int $id)
    {
        $this->dbConnection->createCommand()
            ->delete(
                'tickets_messages',
                ['id' => $id]
            )
            ->execute();
    }
}