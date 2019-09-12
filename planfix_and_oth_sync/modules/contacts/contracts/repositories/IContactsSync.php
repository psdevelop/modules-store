<?php

namespace app\modules\contacts\contracts\repositories;

use app\models\sync\SyncBase;
use app\models\cabinet\ContactsPfSyncJob;

/**
 * Interface IContactsSync
 * Интерфейс, описывающий класс репозитория,
 * работающего с таблицей задач на синхронизацию
 * контактов
 * @package app\modules\contacts\contracts\repositories
 */
interface IContactsSync
{
    /**
     * Запрашивает и возвращает список задач на
     * синхронизацию контактов определенного типа
     * и статуса
     * @param string $syncType
     * @param string $status
     * @return ContactsPfSyncJob[]
     */
    public function getJobs($syncType = 'add', $status = 'new'): array;

    /**
     * Создает объект синхронизации контакта
     * @param ContactsPfSyncJob $job
     * @return SyncBase
     */
    public function getSyncObject(ContactsPfSyncJob $job): SyncBase;

    /**
     * Возвращает тип контакта для синхронизации
     * @param ContactsPfSyncJob $job
     * @return string
     */
    public function getJobContactType(ContactsPfSyncJob $job): string;
}
