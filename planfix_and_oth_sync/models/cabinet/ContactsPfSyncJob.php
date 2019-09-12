<?php
/**
 * @author Poltarokov Stanislav <sp@leads.su> - LEADS.SU
 * Date: 23.04.2019
 */

namespace app\models\cabinet;

use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixUsers;

/**
 * Class ContactsPfSyncJob
 * Класс модели задачи синхронизации контактов
 * @property integer $id
 * @property int $account_id
 * @property string $account_type
 * @property string $sync_type
 * @property string $status
 * @property int $created
 * @property int $modified
 *
 * @property CabinetBase $contact
 *
 * @package app\models
 */
class ContactsPfSyncJob extends CabinetBase
{
    /** @const string */
    const SYNC_TYPE_ADD = 'add';

    /** @const string */
    const SYNC_TYPE_UPDATE = 'update';

    /** @const string */
    const CONTACT_TYPE_AFFILIATE = 'affiliate';

    /** @const string */
    const CONTACT_TYPE_ADVERTISER = 'advertiser';

    /** @const string */
    const CONTACT_USER_CLASS = 'User';

    /** @const string */
    const CONTACT_COMPANY_CLASS = 'Company';

    /** @const string */
    const SYNC_STATUS_NEW = 'new';

    /** @const string */
    const SYNC_STATUS_COMPLETED = 'completed';

    /** @const string */
    const SYNC_STATUS_ERROR = 'error';

    public static $table = 'contacts_pf_sync_jobs';

    /**
     * Возвращает объект контакта из задачи
     * синхронизации
     * @return CabinetBase|null
     */
    public function getContact()
    {
        $this->setDbById();
        $modelClass = $this->getContactModelClass();
        return $modelClass::find()
            ->where(['id' => $this->account_id])
            ->one();
    }

    /**
     * Устанавливает статус задачи на синхронизацию
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
        $this->save();
    }

    /**
     * Возвращает объект синхронизации для
     * синхронизируемого контакта
     * @return SyncBase
     */
    public function getSyncObject(): SyncBase
    {
        $syncModelClass = strpos($this->account_type, '_user') !== false
            ? SyncPlanfixUsers::class
            : SyncPlanfixCompanies::class;

        $this->setDbById();
        $syncObject = $syncModelClass::find()
            ->where(['=', 'leads_id', $this->account_id])
            ->orWhere(['=', 'trade_id', $this->account_id])
            ->one();

        if ($syncObject) {
            return $syncObject;
        }

        return new $syncModelClass();
    }

    /**
     * Возвращает класс модели синхронизируемого контакта
     * @return mixed
     */
    private function getContactModelClass()
    {
        $contactClassesMap = [
            'affiliate' => CabinetAffiliate::class,
            'affiliate_user' => CabinetAffiliateUser::class,
            'advertiser' => CabinetAdvertiser::class,
            'advertiser_user' => CabinetAdvertiserUser::class,
        ];

        return $contactClassesMap[$this->account_type];
    }
}
