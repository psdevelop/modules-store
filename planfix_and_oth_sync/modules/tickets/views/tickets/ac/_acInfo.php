<?php

use app\modules\tickets\models\cabinet\ExternalCabinetInfo;

/** @var ExternalCabinetInfo $cabinetInfo */
?>

<ul>
    <li>Сеть: <?= $cabinetInfo->networkTitle?> </li>
    <li>Логин: <?= $cabinetInfo->login?> </li>

    <?php if ($cabinetInfo->lastName || $cabinetInfo->name || $cabinetInfo->middleName) : ?>
        <li>ФИО: <?= $cabinetInfo->lastName ?> <?= $cabinetInfo->name ?> <?= $cabinetInfo->middleName ?></li>
    <?php endif ?>

    <?php if ($cabinetInfo->phone) : ?>
        <li>Телефон: <?= $cabinetInfo->phone?> </li>
    <?php endif ?>

    <?php if ($cabinetInfo->email) : ?>
        <li>E-mail: <?= $cabinetInfo->email?> </li>
    <?php endif ?>

    <?php if ($cabinetInfo->additional) : ?>
        <li>Дополнительные данные: <?= $cabinetInfo->additional?> </li>
    <?php endif ?>
</ul>
