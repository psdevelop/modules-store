<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception|\app\exceptions\LinkException */

use yii\helpers\Html;
use app\exceptions\LinkException;
if ($exception instanceof LinkException) {
    $helpView = $exception->getHelpView();
    $errors = $exception->getErrors();
}
$this->title = $name;
?>

<div class="site-error">

    <h1 class="text-danger"><?= Html::encode($this->title) ?></h1>

    <h5>
        <?= nl2br(Html::encode($message)) ?>
    </h5>

    <?php if (!empty($errors)) {
        ; ?>
        <h3>
            Ошибки
        </h3>

        <?php foreach ($errors as $key => $error): ?>
            <div class="alert alert-danger">
                <b>Поле: <?= $key; ?></b>
                <br><?= $error[0]; ?>
            </div>
        <?php endforeach; ?>
    <?php }; ?>

    <?php if (!empty($helpView)) {
        try {
            echo $this->render($helpView);
        } catch (Exception $exception) {
            echo "Неверно указан файл справки!";
        }
    }
    ?>
</div>
