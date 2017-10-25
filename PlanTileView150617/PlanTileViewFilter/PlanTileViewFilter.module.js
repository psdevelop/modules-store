define('js!SBIS3.Plan.PlanTileViewFilter', [
   'js!SBIS3.CONTROLS.CompoundControl',
   'html!SBIS3.Plan.PlanTileViewFilter',
   'js!WS.Data/Source/SbisService',
   'css!SBIS3.Plan.PlanTileViewFilter',
   'js!SBIS3.Employee.StaffChoice',
   'js!SBIS3.Staff.Choice',
   'js!SBIS3.CONTROLS.CheckBox',
   'js!SBIS3.Milestones.MilestoneSelectorDialog'
], function (CompoundControl, dotTplFn, SbisService) {

   /**
    * SBIS3.Plan.PlanTileViewFilter
    * @class SBIS3.Plan.PlanTileViewFilter
    * @extends $ws.proto.CompoundControl
    * @author Полтароков Станислав
    */
   var moduleClass = CompoundControl.extend(/** @lends SBIS3.Plan.PlanTileViewFilter.prototype */{
      _dotTplFn: dotTplFn,
      $protected: {
         _options: {
            phaseId: [null]
         }
      },

      init: function () {
         moduleClass.superclass.init.call(this);

         var self = this; // Сохраняем указатель на исходный компонент

         // Создаём обработчик на изменение поле контекста
         this.subscribeTo(this.getContext(), 'onFieldChange', function (e, key, value) {
            // Здесь формируются значения, которое передаётся в качестве значений для строки рядом с кнопой фильтров
            if (self.getContext().getValue('ГруппировкаПоПроектам') == null) {
               self.getContext().setValue('ГруппировкаПоПроектам', false);
            }
            if (self.getContext().getValue('Принадлежность') == null) {
               self.getContext().setValue('Принадлежность', 0);
            }

            self.getContext().setValue('ПринадлежностьПодпись', '');
            self.getContext().setValue('ГруппировкаПоПроектамПодпись', '');

         });

      }

   });

   return moduleClass;
});
