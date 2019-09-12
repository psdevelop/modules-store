<?php
/**
 * @var $offer \app\models\cabinet\CabinetOffer
 * @var $domain \app\models\cabinet\CabinetTrackingDomain
 */
?>

<p>Настройки и подключение нового трекингового домена<br>
    <strong><?= $domain->domain ?? null; ?> </strong>для оффера
    <strong>
        <a href="<?=$offerUrl?>" target="_blank">
            <?= $offerPrefix ?? null; ?>, <?= $offer->name ?? null; ?>
        </a>
    </strong>
    <br>
    <br>
    Для подключения нового трекингового домена рекламодателю необходимо настроить один из трех вариантов интеграции:<br>
</p>
<ul>
    <li>Настройка постбека <strong>(наиболее предпочтительный вариант)</strong></li>
    <li>Установка на странице спасибо JSScriptPixel</li>
    <li>Установка еще одного iframe или img с вызовом нового трекингового домен</li>
</ul>