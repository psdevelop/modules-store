<?php

use app\modules\tickets\models\cabinet\ExternalTicket;
use yii\base\Controller;
use app\modules\tickets\enum\TicketAccountTypeEnum;

/** @var ExternalTicket $ticket*/
/** @var Controller $this */
/** @var $domainUrlAccountWebmaster */
/** @var $domainUrlAccountAdvertise */
/** @var $domainUrlTicket */

?>

<p>
    Название: <?= $ticket->title ?>
    <?= $ticket->managerId ? '(создано менеджером)' : '' ?>
</p>
<p>
    Пользователь:
    <a href="<?= $ticket->getLinkAccount($domainUrlAccountWebmaster, $domainUrlAccountAdvertise) ?>" target="_blank">
        <?= $ticket->accountId ?>, <?= $ticket->accountCompany ?>
    </a>
</p>
<p>Тип пользователя: <?= TicketAccountTypeEnum::getTitleByType($ticket->accountType) ?> </p>
<p>Ссылка на тикет: <?= $ticket->getLinkOnTicket($domainUrlTicket) ?></p>