<?php
/**
 * Created by PhpStorm.
 * User: bengraf
 * Date: 17.10.17
 * Time: 16:21
 */

namespace app\commands;

use app\controllers\traits\LinkTrait;
use app\exceptions\LinkException;
use app\models\cabinet\CabinetBase;
use app\models\cabinet\CabinetOfferToDomains;
use app\models\links\LinkBase;
use app\models\links\LinkOfferTask;
use app\models\sync\SyncBase;
use app\models\sync\SyncPlanfixOffersTasks;
use yii\console\Controller;

class ServiceController extends Controller
{
    use LinkTrait;

    public function actionUpdateTrkTasks()
    {
        /**
         * @var $offersToTrackingDomainsSync SyncPlanfixOffersTasks[]
         */
        $offersToTrackingDomainsSync = SyncPlanfixOffersTasks::find()->all();
            foreach ($offersToTrackingDomainsSync as $syncObject){
                /**
                 * @var $offersToTrackingDomain CabinetOfferToDomains
                 */
                $offersToTrackingDomain = $syncObject->getSyncCabinetObject();
                $model = LinkOfferTask::make([
                    'platform' => $offersToTrackingDomain->base,
                    'offerId' => $offersToTrackingDomain->offer->id,
                    'domain' => $offersToTrackingDomain->domain->domain,
                    'target' => LinkBase::TARGET_PLANFIX,
                    'handler' => 'updateAndOpen',
                ]);
                $this->id = LinkBase::MODULE_API;
                $this->handleLinkModel($model);
            }
    }
}
