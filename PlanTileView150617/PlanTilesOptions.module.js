define('js!SBIS3.Plan.PlanTilesOptions', [
   'js!SBIS3.CORE.Control',
   'Core/EventBus',
   'Core/ParallelDeferred'
], function (CControl, cEventBus, cParallelDeferred) {
   /**
    * SBIS3.Plan.PlanTilesOptions
    * @class SBIS3.Plan.PlanTilesOptions
    * @author Полтароков С.П.
    */

   cEventBus.channel('navigation').subscribe('onNavigate', function (event, regionId, elementId) {
      if (regionId !== 'plan_settings') {
         return;
      }

      var
         mainArea = CControl.ControlStorage.getByName('nav-acc-tpl-area'),
         pD = new cParallelDeferred();

      pD.push(mainArea.waitChildControlByName('Шаблон кнопок'));

      pD.push(mainArea.waitChildControlByName('ШаблонБраузеров'));

      pD.done().getResult().addCallback(function (controls) {
         switch (elementId) {
            case 'tileSettings':
               controls[0].hide();
               controls[0].setTemplate('');
               controls[1].setTemplate('js!SBIS3.Plan.PlanTileViewSettingsDialog');
               break;
         }
      });
   });
});
