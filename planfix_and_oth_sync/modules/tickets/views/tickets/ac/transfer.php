<?php

use app\modules\tickets\models\cabinet\ExternalTicket;
use yii\web\View;

/** @var ExternalTicket $ticket*/
/** @var View $this */
/** @var $domainUrlAccountWebmaster */
/** @var $domainUrlAccountAdvertise */
/** @var $domainUrlTicket */

?>

<?= $this->render('../base', [
    'ticket' => $ticket,
    'domainUrlAccountWebmaster' => $domainUrlAccountWebmaster,
    'domainUrlAccountAdvertise' => $domainUrlAccountAdvertise,
    'domainUrlTicket' => $domainUrlTicket,
]); ?>

<p>Сумма: <?= $ticket->additionalInformation->externalAmount ?></p>

<p><b>Откуда</b></p>
<?= $this->render('_acInfo', ['cabinetInfo' => $ticket->additionalInformation->cabinetInfoSource]); ?>

<p><b>Куда</b></p>
<?= $this->render('_acInfo', ['cabinetInfo' => $ticket->additionalInformation->cabinetInfoDestination]); ?>

<?= $this->render('_ticketComment', ['ticket' => $ticket]) ?>