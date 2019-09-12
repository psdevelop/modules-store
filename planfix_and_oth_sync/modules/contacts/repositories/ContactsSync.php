<?php

namespace app\modules\contacts\repositories;

use app\models\cabinet\CabinetAdvertiserUser;
use app\models\cabinet\CabinetBase;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixUsers;
use app\modules\contacts\contracts\repositories\IContactsSync;
use Yii;
use yii\db\Connection;
use app\models\cabinet\CabinetAffiliateUser;
use app\models\cabinet\ContactsPfSyncJob;
use app\models\cabinet\CabinetAffiliate;
use app\models\cabinet\CabinetAdvertiser;
use app\models\sync\SyncPlanfixCompanies;

/**
 * Class ContactsSync
 * Абстрактный для проектов класс репозитория, оперирующего
 * с данными задач на синхронизацию контактов в проектах
 * @package app\modules\contacts\repositories
 */
abstract class ContactsSync implements IContactsSync
{
    /**
     * Возвращает имя соединения с БД
     * @return string
     */
    public abstract function getConnectionName(): string;

    /** @inheritDoc */
    public function getJobs($syncType = ContactsPfSyncJob::SYNC_TYPE_ADD, $status = ContactsPfSyncJob::SYNC_STATUS_NEW): array
    {
        ContactsPfSyncJob::setDb($this->getConnectionName());
        return ContactsPfSyncJob::find()
            ->where(['sync_type' => $syncType, 'status' => $status])
            ->all();
    }

    /** @inheritDoc */
    public function getSyncObject(ContactsPfSyncJob $job): SyncBase
    {
        $isBlackProject = $this->isBlackProject();
        $syncObject = $job->getSyncObject();

        if ($syncObject->isNewRecord) {
            $syncObject->status_sync = 'add';
        }

        if ($isBlackProject) {
            $syncObject->trade_id = $job->account_id;
        } else {
            $syncObject->leads_id = $job->account_id;
        }

        $contact = $job->getContact();

        if ($syncObject instanceof SyncPlanfixUsers) {
            $this->syncUserContact($contact, $syncObject);
        } elseif ($syncObject instanceof SyncPlanfixCompanies) {
            $this->syncCompanyContact($contact, $syncObject);
        }

        $syncObject->type = strpos($job->account_type, 'affiliate') !== false
            ? ContactsPfSyncJob::CONTACT_TYPE_AFFILIATE
            : ContactsPfSyncJob::CONTACT_TYPE_ADVERTISER;
        $syncObject->save();

        return $syncObject;
    }

    /** @inheritDoc */
    public function getJobContactType(ContactsPfSyncJob $job): string
    {
        return strpos($job->account_type, '_user') !== false
            ? ContactsPfSyncJob::CONTACT_USER_CLASS
            : ContactsPfSyncJob::CONTACT_COMPANY_CLASS;
    }

    /**
     * Возвращает признак работы с проектом black
     * @return bool
     */
    public function isBlackProject()
    {
        return $this->getConnectionName() === 'dbTradeLeads';
    }

    /**
     * Заполняет объект синхронизации для контакта типа User
     * @param CabinetBase $contact
     * @param SyncPlanfixUsers $syncObject
     */
    private function syncUserContact(CabinetBase $contact, SyncPlanfixUsers $syncObject)
    {
        $isBlackProject = $this->isBlackProject();

        /**
         * @var Connection $syncConnection
         * Соединение для поиска контакта в другом бренде
         */
        $syncConnection = $isBlackProject
            ? Yii::$app->dbLeads
            : Yii::$app->dbTradeLeads;

        $contactTableName = '';
        $companyIdFieldName = '';
        $companyId = null;

        if ($contact instanceof CabinetAffiliateUser) {
            $contactTableName = CabinetAffiliateUser::$table;
            $companyIdFieldName = 'affiliate_id';
            $companyId = $contact->affiliate_id;
        }

        if ($contact instanceof CabinetAdvertiserUser) {
            $contactTableName = CabinetAdvertiserUser::$table;
            $companyIdFieldName = 'advertiser_id';
            if (!$companyId) {
                $companyId = $contact->advertiser_id;
            }
        }

        if ($isBlackProject) {
            $syncObject->trade_cid = $companyId;
        } else {
            $syncObject->leads_cid = $companyId;
        }

        if (!$contactTableName) {
            return;
        }

        $syncContact = $syncConnection->createCommand("SELECT id, $companyIdFieldName FROM $contactTableName WHERE email = :email")
            ->bindValue(':email', $contact->email)
            ->queryOne();

        if (!$syncContact) {
            return;
        }

        $syncCompanyFieldName = $isBlackProject ? 'leads_cid' : 'trade_cid';
        if (!$syncObject->$syncCompanyFieldName && $syncContact->$companyIdFieldName) {
            $syncObject->$syncCompanyFieldName = $syncContact->$companyIdFieldName;
        }

        $contactIdFieldName = $isBlackProject ? 'leads_id' : 'trade_id';
        //TODO разобраться почему валится привязка к менеджеру
        if (!$syncObject->$contactIdFieldName && $syncContact->id && false) {
            $syncObject->$contactIdFieldName = $syncContact->id;
        }
    }

    /**
     * Заполняет объект синхронизации для контакта типа Company
     * @param CabinetBase $contact
     * @param SyncPlanfixCompanies $syncObject
     */
    private function syncCompanyContact(CabinetBase $contact, SyncPlanfixCompanies $syncObject)
    {
        $isBlackProject = $this->isBlackProject();

        /**
         * @var Connection $syncConnection
         * Соединение для поиска контакта в другом бренде
         */
        $syncConnection = $isBlackProject
            ? Yii::$app->dbLeads
            : Yii::$app->dbTradeLeads;

        $companyTableName = '';

        if ($contact instanceof CabinetAffiliate) {
            $companyTableName = CabinetAffiliate::$table;
        } elseif ($contact instanceof CabinetAdvertiser) {
            $companyTableName = CabinetAdvertiser::$table;
        }

        //TODO исследовать проблему зависания кода ниже
        if (!$companyTableName || true) {
            return;
        }

        $syncCompanyId = $syncConnection->createCommand()
            ->select('id')
            ->from($companyTableName)
            ->where('phone = :phone', [':phone' => $contact->phone])
            ->queryScalar();

        if (!$syncCompanyId) {
            return;
        }

        $contactIdFieldName = $isBlackProject ? 'leads_id' : 'trade_id';
        if (!$syncObject->$contactIdFieldName) {
            $syncObject->$contactIdFieldName = $syncCompanyId;
        }
    }
}
