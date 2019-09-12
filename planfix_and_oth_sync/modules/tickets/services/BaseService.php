<?php

namespace app\modules\tickets\services;

use Yii;
use yii\db\Connection;
use yii\db\Transaction;

class BaseService
{
    /** @var Connection */
    protected $dbLeads;

    /** @var Connection */
    protected $dbBlack;

    /** @var Connection */
    protected $dbLocal;

    /** @var Connection[] */
    protected $allDb;

    /** @var Transaction[] */
    protected $transactions;

    public function __construct()
    {
        $this->dbLeads = Yii::$app->dbLeads;
        $this->dbBlack = Yii::$app->dbTradeLeads;
        $this->dbLocal = Yii::$app->dbPlanfixSync;

        $this->allDb = [$this->dbLocal, $this->dbLeads];//, $this->dbBlack];
        $this->transactions = [];
    }

    public function transactionStart()
    {
        foreach ($this->allDb as $db) {
            $this->transactions[] = $db->beginTransaction();
        }
    }

    public function transactionCommit()
    {
        foreach ($this->transactions as $transaction) {
            $transaction->commit();
        }

        $this->transactions = [];
    }

    public function transactionRollback()
    {
        foreach($this->transactions as $transaction) {
            $transaction->rollBack();
        }

        $this->transactions = [];
    }
}
