<?php

namespace app\modules\tickets\models\cabinet;

use app\modules\tickets\helpers\MapperTicketPlanfix;
use app\modules\tickets\models\BaseModel;
use DateTimeImmutable;
use yii\helpers\Html;

class ExternalTicketMessage extends BaseModel
{
    /** @var int */
    public $id;

    /** @var int */
    public $accountId;

    /** @var string */
    public $accountType;

    /** @var string */
    public $message;

    /** @var int */
    public $ticketId;

    /** @var DateTimeImmutable */
    public $created;

    /** @var DateTimeImmutable */
    public $modified;

    public static function normalizeMessage(string $string): string
    {
        $string = str_replace('<br>', "\n\r", $string);
        $string = str_replace('<br/>', "\n\r", $string);
        $string = trim(strip_tags($string));
        $string = Html::decode($string);

        return $string;
    }

}
