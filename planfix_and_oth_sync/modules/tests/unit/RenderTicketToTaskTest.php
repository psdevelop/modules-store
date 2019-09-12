<?php

use app\modules\tickets\helpers\RenderTicketToTask;
use app\modules\tickets\models\cabinet\ExternalTicket;
use Codeception\Test\Unit;

class RenderTicketToTaskTest extends Unit
{
    public function testRenderTitle()
    {
        $allTypeTickets = $this->getAllTypeTickets();

        foreach ($allTypeTickets as $item) {
            $externalTicket = new ExternalTicket($item['ticket']);

            $this->assertEquals($item['expectedTitle'], RenderTicketToTask::renderTitle($externalTicket));
        }
    }

    public function testRenderDescription()
    {
        $allTypeTickets = $this->getAllTypeTickets();

        foreach ($allTypeTickets as $item) {
            $externalTicket = new ExternalTicket($item['ticket']);

            $this->assertEquals(
                $this->removeSpacesAndBreakLine($item['expectedDescription']),
                $this->removeSpacesAndBreakLine(RenderTicketToTask::renderDescription($externalTicket))
            );
        }
    }

    public function getAllTypeTickets()
    {
        return require __DIR__ .'./../_data/AllTypesCabinetTickets.php';
    }

    private function removeSpacesAndBreakLine(string $text): string
    {
        return str_replace(["\r", "\n", " "], '', $text);
    }
}
