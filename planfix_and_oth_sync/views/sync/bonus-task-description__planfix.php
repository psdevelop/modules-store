<?php
use app\models\cabinet\CabinetAffiliate;
use app\models\cabinet\CabinetEmployee;
use app\models\cabinet\CabinetOffer;
use app\components\enums\SystemEnum;
/**
 * @var $client CabinetOffer | CabinetAffiliate | CabinetEmployee
 * @var $amount
 */
?>

<p>Прошу внести приветственный бонус <?= $amount ?>р. на баланс <?= $client->getFullPlanfixName() ?>, за выполнение условий, результат заработка<br>
</p>

<div>
    <a
            style=" background-color: #4daf7d;
                    border: none;
                    color: white;
                    padding: 15px 32px;
                    text-align: center;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 16px;
            "
            href="<?= $client->getCabinetSummaryUrl(
                SystemEnum::GROUPING_YEAR,
                date('d.m.Y', strtotime('-6 month')),
                date('d.m.Y')
            ) ?>" target="_blank">
        Статистика
    </a>
    <a
            style=" background-color: #4d80af;
                    border: none;
                    color: white;
                    padding: 15px 32px;
                    text-align: center;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 16px;
            "
            href="<?= $client->getCabinetUrl() ?>" target="_blank">
        Ссылка в кабинет
    </a>
</div>
