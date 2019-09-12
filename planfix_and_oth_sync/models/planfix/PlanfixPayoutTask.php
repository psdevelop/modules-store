<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 08.06.17
 * Time: 8:48
 */

namespace app\models\planfix;


use app\components\helpers\LogHelper;
use app\models\cabinet\CabinetAffiliate;
use app\models\cabinet\CabinetAffiliateBillingFastPayoutRequest;
use app\models\cabinet\CabinetAffiliateBillingPayoutRequest;
use app\models\cabinet\CabinetEmployee;
use app\models\sync\SyncPlanfixCompanies;
use app\models\sync\SyncPlanfixPayments;

class PlanfixPayoutTask extends PlanfixTask
{

    public $taskPayoutMap;

    /**
     * Срочная выплата. _____р. 123123, Имя веба.
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $template
     * @return bool
     */
    public function getPayoutTaskTitle($payoutRequest, $template)
    {
        /**
         * @var $affiliate CabinetAffiliate
         */
        if (!$affiliate = $payoutRequest->affiliate) {
            return null;
        }
        $affiliate->setBaseIds($payoutRequest);

        if (!is_string($template)) {
            return null;
        }

        /**
         * @var $commission CabinetAffiliateBillingPayoutRequest
         */
        if (!($commission = $payoutRequest->commission)) {
            $commissionString = 'Оплачивается бонусными баллами';
        } else {
            $commissionString = (string)$commission->amount . ' р.';
        }

        $affiliateId = $affiliate->getBasePrefix();
        $affiliateName = $affiliate->company;
        $payoutSum = (float)$payoutRequest->amount;

        return sprintf($template, $payoutSum, $commissionString, $affiliateId, $affiliateName);
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $params
     * @return bool|array
     */
    public function getPayoutClient($payoutRequest, $params)
    {
        /**
         * @var $affiliate CabinetAffiliate
         */
        if (!$affiliate = $payoutRequest->affiliate) {
            return null;
        }
        $affiliate->setBaseIds($payoutRequest);

        /**
         * @var $planfixAffiliate SyncPlanfixCompanies
         */
        if (!$planfixAffiliate = $affiliate->planfix) {
            return null;
        }

        $planfixAffiliateObject = PlanfixContact::findOne([
            'id' => $planfixAffiliate->planfix_id
        ]);

        $planfixId = $planfixAffiliateObject[$params];

        return [
            'id' => $planfixId
        ];
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $params
     * @return array
     */
    public function getPayoutProject($payoutRequest, $params)
    {
        return [
            'id' => $this->projects[$params . ucfirst($payoutRequest->base ?? '')] ?? null
        ];
    }

    /**
     * @param $payoutRequest
     * @param $params
     * @return array
     */
    public function getPayoutTemplate($payoutRequest, $params)
    {
        return $this->templates[$params] ?? null;
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $format
     * @return false|string
     */
    public function getCreated($payoutRequest, $format)
    {
        return date($format, strtotime($payoutRequest->created));
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $params
     * @return bool
     */
    public function getStartDateIsSet($payoutRequest, $params)
    {
        return (bool)$payoutRequest->created;
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $params
     * @return array
     */
    public function getWorkers($payoutRequest, $params)
    {
        /**
         * @var $employee CabinetEmployee
         */
        if (!$employee = $payoutRequest->employee) {
            return null;
        }

        $planfixUsers = PlanfixBase::instance()->planfixUsers;

        if (getenv('app_config') == 'development') {
            $emails = [
                'af@leads.su_fake',
                'rbp@leads.su_fake',
                'vp@leads.su'
            ];
            $employee->email = $emails[array_rand($emails)];
        }

        if (!$planfixUser = $planfixUsers[$employee->email] ?? null) {
            return null;
        }

        return [
            'users' => [
                'id' => $planfixUser['id']
            ]
        ];
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $template
     * @return array
     */
    public function getPayoutTaskDescription($payoutRequest, $template)
    {
        if (!is_string($template)) {
            return null;
        }

        /**
         * @var $commission CabinetAffiliateBillingPayoutRequest
         */
        if (!($commission = $payoutRequest->commission)) {
            $commissionString = 'Оплачивается бонусными баллами';
        } else {
            $commissionString = (string)$commission->amount . ' р.';
        }


        return sprintf(
            $template,
            (float)$payoutRequest->amount,
            $commissionString,
            (string)$payoutRequest->affiliate_comment
        );
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $params
     * @return bool
     */
    public function getMembers($payoutRequest, $params)
    {
        return null;
    }

    /**
     * @param $payoutRequest CabinetAffiliateBillingFastPayoutRequest
     * @param $params
     * @return bool
     */
    public function getAuditors($payoutRequest, $params)
    {
        return null;
    }


    /**
     * "Заятнуть" из Планфикса таски требующие синхронизации по запросам и транспортировать в кабинет
     */
    public function sendPaymentsPlanfixToCabinet()
    {
        // Получаем все запросы из Планфикса для синхронизации
        $inProcessPlanfixRequests =
            PlanfixBase::catchCustomValues(
                PlanfixPayoutTask::find([
                    'target' => $this->filters['finalizedPayoutRequests'],
                ]),
                [
                    $this->customFields['note'] => 'note'
                ]
            );
        LogHelper::info("PULL START ");

        // Проверяем наличие запросов для обновления в Планфиксе и таблице Синхронизации с кабинетом
        if (!count($inProcessPlanfixRequests)) {
            LogHelper::info("Нет запросов для синхронизации");
            return false;
        }

        foreach ($inProcessPlanfixRequests as $planfixId => $inProcessPlanfixRequest) {
            /**
             * @var $syncRequest SyncPlanfixPayments
             */
            if (!$syncRequest = SyncPlanfixPayments::findOne(['planfix_id' => $planfixId])) {
                LogHelper::info("Запрос PF:$planfixId не найден в Кабинете!");
                continue;
            }
            /**
             * @var $baseCabinetRequestObject CabinetAffiliateBillingFastPayoutRequest
             */
            $baseCabinetRequestObject = $syncRequest->getSyncCabinetObject();

            if ($baseCabinetRequestObject->status == CabinetAffiliateBillingFastPayoutRequest::STATUS_PAYMENT_PAID) {
                $syncRequest->status_payment = CabinetAffiliateBillingPayoutRequest::STATUS_PAYMENT_PAID;
                $syncRequest->save();
                $this->moveToPaid($planfixId);
                continue;
            }

            if (!$newStatus = $this->sendPaymentPlanfixToCabinet($inProcessPlanfixRequest, $baseCabinetRequestObject)) {
                continue;
            }

            $syncRequest->status_payment = $newStatus;
            if (!$syncRequest->save()) {
                LogHelper::info("Запрос PF:$planfixId не удалось синхронизировать...");
                continue;
            }
        }
        return true;
    }

    /**
     * Затянуть один запрос в Кабинет
     * @param $planfixRequest
     * @param $cabinetRequest CabinetAffiliateBillingFastPayoutRequest
     * @return bool|null
     */
    public function sendPaymentPlanfixToCabinet($planfixRequest, $cabinetRequest)
    {
        // Статус из планфикса
        $planfixRequestStatus = (int)$planfixRequest['status'] ?? null;
        $planfixId = $planfixRequest['id'] ?? null;
        $cabinetApi = $this->cabinetApi;

        // Маппим статус
        if (!$newStatus = CabinetAffiliateBillingPayoutRequest::$statusesPlanfixCabinet[$planfixRequestStatus] ?? false) {
            LogHelper::warning("Несинхронизируемый статус PF:$planfixRequestStatus.");
            return false;
        }

        /**
         * Если статус в Кабинете == Отклонен, а в Планфикс == Выполнен,
         *      то ОТМЕНЯЕМ запрос в Планфиксе
         */
        if ($cabinetRequest->status == CabinetAffiliateBillingPayoutRequest::STATUS_PAYMENT_REJECTED && $newStatus == CabinetAffiliateBillingPayoutRequest::STATUS_PAYMENT_APPROVED) {
            return $this->moveToStatus(
                $planfixId,
                PlanfixPayoutTask::TASK_STATUS_CANCELED,
                "Запрос отклонен в Кабинете!"
            );
        }

        if ($hasFinalStatus = $cabinetRequest->hasFinalStatus() && !$isPaid = $cabinetRequest->isPaid()) {
            if ($newStatus != $cabinetRequest->status) {
                $this->moveToStatus(
                    $planfixId,
                    CabinetAffiliateBillingPayoutRequest::$statusesCabinetPlanfix[$cabinetRequest->status],
                    "Согласование запроса на выплату завершено! Статус и заметка не могут быть изменены!"
                );
            }
        }
        // Основное тело заметки:
        $taskNote = $this->planfixWs->getTaskNote($planfixRequest);

        // Изменения статусов
        $planfixChangeActions = PlanfixActionTask::getStatusChangeActions($planfixId);
        krsort($planfixChangeActions);
        foreach ($planfixChangeActions as $action) {
            $taskOwner = $action['owner']['name'] ?? '(не определено)';
            $actionStatus = $action['statusChange']['newStatus'] ?? null;

            $isApproveAction = in_array($actionStatus, [
                PlanfixPayoutTask::TASK_STATUS_DONE,
            ]);

            $isRejectAction = in_array($actionStatus, [
                PlanfixPayoutTask::TASK_STATUS_REJECTED,
                PlanfixPayoutTask::TASK_STATUS_CANCELED
            ]);

            if ($isApproveAction) {
                $actionRuStatus = 'Согласован';
            }
            if ($isRejectAction) {
                $actionRuStatus = 'Отклонен';
            }
            if (!isset($actionRuStatus)) {
                continue;
            }

            $taskNote .= $actionRuStatus ? "<br>$actionRuStatus: $taskOwner" : "";
            unset($actionRuStatus, $isApproveAction, $isRejectAction);
        }

        if ($equals = $taskNote == $cabinetRequest->employee_note && $newStatus == $cabinetRequest->status) {
            LogHelper::info("Запрос не изменен...");
            return false;
        }

        if ($newStatus == CabinetAffiliateBillingPayoutRequest::STATUS_PAYMENT_NEED_APPROVE) {
            return false;
        }

        // Запрос на кабинет-API
        $cabinetApi
            ->run($cabinetRequest->base)
            ->updateBillingRequest(
                $cabinetRequest->id,
                $newStatus,
                htmlentities($taskNote)
            );

        if ($error = $cabinetApi->getError()) {
            $column = array_column($error->errorParams, 'name');
            if (in_array('status', $column)) {
                LogHelper::critical("ДЛЯ ЗАПРОСА $cabinetRequest->id НЕЛЬЗЯ УСТАНОВИТЬ СТАТУС $newStatus!");
                return false;
            }
            LogHelper::critical("Ошибка API Кабинета!");
            LogHelper::critical(json_encode($this->cabinetApi->getResponse(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            LogHelper::critical("---------------end");
            return false;
        }

        if(!$cabinetApi->getBody()){
            LogHelper::critical("Нет ответа от API Кабинета!");
            return false;
        }

        $finalMessage = "Запрос PF:$planfixId синхронизирован со статусом " . CabinetAffiliateBillingPayoutRequest::getRuStatus($newStatus);
        LogHelper::info($finalMessage);
        return $newStatus;
    }

    /**
     * @param $planfixId
     * @return bool
     */
    public function moveToPaid($planfixId)
    {
        return $this->moveToStatus($planfixId, PlanfixPayoutTask::TASK_STATUS_COMPLETED, "Запрос оплачен!");
    }

    /**
     * @param $planfixId
     * @param $planfixStatus
     * @param $comment
     * @return mixed
     */
    public function moveToStatus($planfixId, $planfixStatus, $comment)
    {
        $finalPlanfixAction = new PlanfixActionTask();
        $finalPlanfixAction->description = "<b style='color: #0051a1'>$comment</b >";
        $finalPlanfixAction->task = ['id' => $planfixId];
        $finalPlanfixAction->isHidden = 0;
        $finalPlanfixAction->taskNewStatus = $planfixStatus;
        $finalPlanfixAction->notifiedList = 0;

        if ($finalPlanfixAction->add()) {
            LogHelper::info('Добавлен коментарий задаче в Планфикс');
        }
        return $finalPlanfixAction->taskNewStatus;
    }

}