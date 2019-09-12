<?php

use app\modules\tickets\models\cabinet\ExternalTicket;

/** @var ExternalTicket $ticket*/

?>

<p><b>Комментарий пользователя:<b></p>
<p><?= $ticket->description ?? '-' ?></p>