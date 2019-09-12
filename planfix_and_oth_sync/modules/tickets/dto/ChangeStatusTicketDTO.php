<?php

namespace app\modules\tickets\dto;

class ChangeStatusTicketDTO
{
    /** @var string */
    public $type;

    /** @var int */
    public $ticketId;

    /** @var string */
    public $status;
}