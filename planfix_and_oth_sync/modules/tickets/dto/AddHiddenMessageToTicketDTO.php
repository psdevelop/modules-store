<?php

namespace app\modules\tickets\dto;

/**
 * Class AddHiddenMessageToTicketDTO
 * @package app\modules\tickets\dto
 */
class AddHiddenMessageToTicketDTO
{
    /** @var string */
    public $type;

    /** @var string */
    public $messageText;

    /** @var int */
    public $ticketId;
}
