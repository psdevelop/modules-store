<?php

namespace app\modules\tickets\helpers;

use app\modules\tickets\enum\TicketCategoryEnum;
use app\modules\tickets\enum\TicketCategorySubEnum;
use app\modules\tickets\models\cabinet\ExternalTicket;
use app\modules\tickets\services\EnvironmentService;
use Yii;

class RenderTicketToTask
{
    private static $mapTaskRender = [
        TicketCategoryEnum::CATEGORY_AK => [
            TicketCategorySubEnum::SUB_REPLENISHMENT => 'replenishment',
            TicketCategorySubEnum::SUB_CREATE => 'creature',
            TicketCategorySubEnum::SUB_TRANSFER => 'transfer',
            TicketCategorySubEnum::SUB_OTHER => 'other',
            TicketCategorySubEnum::SUB_MODERATION => 'moderation',
        ],
        TicketCategoryEnum::CATEGORY_TP => [
            TicketCategorySubEnum::SUB_FREE_FORM => 'freeform',
        ],
    ];

    public static function renderTitle(ExternalTicket $externalTicket): string
    {
        return sprintf(
            '#%s - %s',
            $externalTicket->id,
            MapperTicketPlanfix::getTitleTask($externalTicket->category, $externalTicket->subcategory));
    }

    public static function renderDescription(ExternalTicket $externalTicket): string
    {
        $view = sprintf(
            Yii::getAlias('@app/modules/tickets/views/tickets/ac/%s.php'),
            self::$mapTaskRender[$externalTicket->category][$externalTicket->subcategory]
        );

        $domainUrlAccountWebmaster = $externalTicket->project === EnvironmentService::PROJECT_LEADS ?
        env('LEADS_SU_LINK_ON_WEBMASTER') : env('LEADS_BLACK_LINK_ON_WEBMASTER');
        $domainUrlAccountAdvertise = $externalTicket->project === EnvironmentService::PROJECT_LEADS ?
            env('LEADS_SU_LINK_ON_ADVERTISE') : env('LEADS_BLACK_LINK_ON_ADVERTISE');
        $domainUrlTicket = $externalTicket->project === EnvironmentService::PROJECT_LEADS ?
            env('LEADS_SU_LINK_ON_TICKET') : env('LEADS_BLACK_LINK_ON_TICKET');

        return Yii::$app->view->renderFile(
            $view,
            [
                'ticket' => $externalTicket,
                'domainUrlAccountWebmaster' => $domainUrlAccountWebmaster,
                'domainUrlAccountAdvertise' => $domainUrlAccountAdvertise,
                'domainUrlTicket' => $domainUrlTicket,
            ]
        );
    }
}
