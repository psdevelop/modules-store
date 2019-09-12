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

<p><b>Данные агентского кабинета</b></p>
<?= $this->render('_acInfo', ['cabinetInfo' => $ticket->additionalInformation->cabinetInfo]); ?>

<?= $this->render('_ticketComment', ['ticket' => $ticket]) ?>