define('js!SBIS3.Plan.PlanTileView', [
   'Core/core-instance',
   'Core/CommandDispatcher',
   'Core/helpers/fast-control-helpers',
   'Deprecated/helpers/fast-control-helpers',
   'Deprecated/helpers/collection-helpers',
   'Core/helpers/random-helpers',
   'Core/helpers/string-helpers',
   'Core/UserInfo',
   'Core/UserConfig',
   'Core/core-merge',
   'Core/ClientsGlobalConfig',
   'js!SBIS3.CORE.CompoundControl',
   'html!SBIS3.Plan.PlanTileView',
   'js!WS.Data/Source/Memory',
   'js!WS.Data/Collection/RecordSet',
   'js!WS.Data/Entity/Record',
   'js!WS.Data/Adapter/Sbis',
   'js!SBIS3.Plan.PlanTileViewModel',
   'js!WS.Data/Source/SbisService',
   'js!WS.Data/Query/Query',
   'js!SBIS3.EDO.DocOpener',
   'js!SBIS3.CONTROLS.Action.SelectorAction',
   'js!SBIS3.CONTROLS.Utils.InformationPopupManager',
   'js!SBIS3.WTM.TimePeriodPicker',
   'js!WS.Data/Entity/Model',
   'js!SBIS3.Person.PersonPhoto',
   'js!SBIS3.Plan.PlanPoints',
   'Core/Context',
   'js!SBIS3.Plan.WorkPlans',
   'Core/RightsManager',
   'js!SBIS3.WTM.EventCardOpener',
   'Core/EventBus',
   'js!SBIS3.CONTROLS.Action.List.ReorderMove',
   'js!SBIS3.Plan.PlanTileViewFilter',
   'tmpl!SBIS3.Plan.PlanTileView/resources/tiledWorkPlansGroupItem',
   'html!SBIS3.Plan.PlanTileView/resources/tiledPlanRowItemContentTemplate',
   'tmpl!SBIS3.Plan.PlanTileView/resources/headColumnTpl',
   'tmpl!SBIS3.Plan.PlanTileView/resources/groupTpl',
   'tmpl!SBIS3.Plan.PlanTileView/resources/breadCrumbsPointTpl',
   'tmpl!SBIS3.Plan.PlanTileView/resources/tilesGroupTemplate',
   'tmpl!SBIS3.Plan.PlanTileView/resources/tilePhotoTemplate',
   'tmpl!SBIS3.Plan.PlanTileView/resources/tileTaskInfoTemplate',
   'tmpl!SBIS3.Plan.PlanTileView/resources/workTimeAddTpl',
   'css!SBIS3.Plan.PlanTileView',
   'js!SBIS3.CONTROLS.BreadCrumbs',
   'js!SBIS3.CONTROLS.IconButton',
   'js!SBIS3.CONTROLS.FastDataFilter'
   ], function (cInstance, CommandDispatcher, fcHelpers, dFCHelpers, dColHelpers, randomHelpers, strHelpers, UserInfo,
                UserConfig, cMerge, ClientsGlobalConfig, CompoundControl, dotTplFn, StaticSource, RecordSet, Record,
                AdapterSbis, PlanTileViewModel, SbisService, Query, DocOpener, SelectorAction, InformationPopupManager,
                TimePeriodPicker, Model, Photo, PlanPoints, cContext, WorkPlans, RightsManager, EventCardOpener,
                EventBus, ReorderMove) {

   /**
    * Проведение непосредственно самого запроса
    * получения данных о временных затратах по пунктам
    * к сервису СБИС, на основании данных пунктов и
    * с размещением ответа в переменных контекста
    * @param ctx
    * @param pointsData
    * @param setInHTML
    * @param browser
    * @returns {*}
    */
   function pointsTimeRequest(ctx, pointsData, setInHTML, browser) {
      var
         filter = ctx.getValue('filter'),
         planStartDate = filter && filter['ФильтрПланДатаНач'],
         planEndDate = filter && filter['ФильтрПланДатаКнц'],
         format = [{
            name: 'Исполнители',
            type: 'array',
            kind: 'integer'
         }, {
            name: 'Документ',
            type: 'integer'
         }],
         pointsRecordSet = new RecordSet({
            format: format,
            adapter: 'adapter.sbis'
         });

      if (pointsData && pointsData.length) {
         dColHelpers.forEach(pointsData, function (point, key) {
            var rec = new Record({
               format: format,
               adapter: 'adapter.sbis'
            });

            rec.set({
               "Исполнители": point[0],
               "Документ": point[1]
            });

            pointsRecordSet.add(rec);
         });
         var sbisServiceSend = {
            filter: {
               'ПунктыПлана': pointsRecordSet,
               'ПланДатаНач': planStartDate,
               'ПланДатаКнц': planEndDate
            },
            setService: new SbisService({
               endpoint: 'СвязьДокументовПлан',
               firstLoad: false
            })
         };

         sbisServiceSend.setService
            .call('ЗатраченноеВремяПлитка', sbisServiceSend.filter)
            .addCallback(function (dataSet) {
               var
                  data = dataSet.getAll(),
                  docId,
                  docTime,
                  timeFormatted,
                  planDocsTimeResources = ctx.getValue('planDocsTimeResources');

               data && data.each(function (dataItem, i) {
                  docId = dataItem.get('Документ');
                  docTime = dataItem.get('ВремяМинут');
                  if (docId && docTime >= 0) {
                     timeFormatted = _getTimeInHMFormat(docTime);
                     planDocsTimeResources[docId] = timeFormatted;
                     /**
                      * TODO Пишем костыль, так как передача
                      * данных в переменную контекста
                      * не отображает их через data-bind
                      */
                     if (setInHTML && browser) {
                        browser.getContainer()
                           .find('.WorkPlan__tiledView__tileElement[data-doc-id=' + docId + '] .tiledView__tileWorkLng > span')
                           .text(timeFormatted);
                     }
                  }
               });
               ctx.setValue('planDocsTimeResources', planDocsTimeResources);
            });
      }
   }

   /*Обновление пункта плана
    * @param linkDocId
    * @returns {*}
    */
   function renewPlanPoint(linkDocId, renewTime, renewInformers) {
      var
         ctx = this.getContext(),
         planPoints = ctx.getValue('planPoints'),
         pointItem,
         point,
         filter,
         pointResDoc,
         docId,
         docCountersSendMsg,
         docCountersSendSubTask,
         docCountersSendMilestone;

      if (planPoints && planPoints[linkDocId]) {

         pointItem = planPoints[linkDocId];
         point = pointItem && pointItem.record;
         filter = ctx.getValue('filter');
         pointResDoc = point && point.get('ДокументСледствие');
         docId = pointResDoc || linkDocId;

         // будущие объекты для отрисовки
         docCountersSendMsg = false; //КоличествоСообщенийПлитка
         docCountersSendSubTask = false; // КоличествоПодзадачПлитка
         docCountersSendMilestone = false; // ВехиПлитка

         if (pointResDoc && renewTime) {
            pointsTimeRequest(ctx, [[point.get('РП.ИдИсполнителей'), pointResDoc]], true, this);
         }

         if (docId && renewInformers) {
            // КоличествоСообщенийПлитка
            docCountersSendMsg = {
               methodName: 'КоличествоСообщенийПлитка',
               docIds: [docId],
               docIdName: 'ИдО',
               valName: 'КоличествоСообщений',
               valContainerClass: '.WorkPlan__tiledView__tileElement__messagesCount .controls-Button__text',
               showedElmClass: null,
               setTitle: false,
               tilesArray: null
            };
            // КоличествоПодзадачПлитка
            if (pointItem.tilePointType === 0) {
               docCountersSendSubTask = {
                  methodName: 'КоличествоПодзадачПлитка',
                  docIds: [docId],
                  docIdName: '@Документ',
                  valName: 'КоличествоПодзадач',
                  valContainerClass: '.WorkPlan__tiledView__tileElement__tasksCount .controls-Button__text',
                  showedElmClass: '.WorkPlan__tiledView__tileElement__tasksCount',
                  setTitle: false,
                  tilesArray: null
               };
            }
            // ВехиПлитка
            docCountersSendMilestone = {
               methodName: 'ВехиПлитка',
               docIds: [docId],
               docIdName: '@Документ',
               valName: 'Веха.Название',
               valContainerClass: '.tiledView__taskMilestoneName',
               showedElmClass: null,
               setTitle: true,
               tilesArray: null
            };
         }

         // рисуем
         docCountersSendMsg && _getDocCountersData(docCountersSendMsg);
         docCountersSendSubTask && _getDocCountersData(docCountersSendSubTask);
         docCountersSendMilestone && _getDocCountersData(docCountersSendMilestone);

      }
   }

   /**
    * Получить информацию о времени в форматированном виде
    * @param time
    * @returns {*}
    */
   function _getTimeInHMFormat(time) {
      if (time) {
         var
            wHours = Math.floor(time / 60),
            wMinutes = time % 60;
         wHours = wHours ? wHours + ':' : '00:';
         wMinutes = wMinutes ? wMinutes + '' : '00';
         return ( wHours.length < 3 ? '0' + wHours : wHours) + ( wMinutes.length < 2 ? '0' + wMinutes : wMinutes);
      }

      return '00:00';
   }

   /**
    * Функция запроса, извлечения из полученного набора данных и отображения счетчиков(полей) в плитке
    * @param obj - объект с опциями
    */
   function _getDocCountersData(obj) {
      var
         dataSourceMsgCounts = new SbisService({
            endpoint: 'СвязьДокументовПлан',
            firstLoad: false
         }),
         methodName = obj.methodName,
         docIds = obj.docIds,
         docIdName = obj.docIdName,
         valName = obj.valName,
         valContainerClass = obj.valContainerClass,
         showedElmClass = obj.showedElmClass,
         setTitle = obj.setTitle,
         tilesArray = obj.tilesArray;

      dataSourceMsgCounts
         .call(methodName, {
            "Документы": docIds
         })
         .addCallback(function (dataSet) {
            var
               dataAssoc = {},
               data = dataSet.getAll(),
               docId,
               $container,
               $containerElm,
               insertText,
               tilesInformersData = {};

            data && data.each(function (dataItem, i) {
               docId = dataItem.get(docIdName);
               if (docId) {
                  dataAssoc[docId] = dataItem.get(valName);
                  if (!tilesInformersData[docId]) {
                     tilesInformersData[docId] = {};
                  }
                  tilesInformersData[docId] = dataItem.get(valName);
               }
            });

            tilesArray && dColHelpers.forEach(tilesArray, function (tileItem, i) {
               docId = tileItem.docId;
               if (tilesInformersData[docId]) {
                  tileItem[valName] = tilesInformersData[docId];
               }
            });

            $('.WorkPlan__tiledView__tileElement').each(function (index, tileContainer) {
               $container = $(tileContainer);
               docId = parseInt($container.data('doc-id'), 10);
               $containerElm = $container.find(valContainerClass);
               if (docId && dataAssoc[docId] && $containerElm && $containerElm.length > 0) {
                  insertText = strHelpers.escapeHtml(strHelpers.escapeTagsFromStr(dataAssoc[docId] + ''));
                  $containerElm.empty().append(insertText);
                  if (setTitle) {
                     $containerElm.attr('title', insertText);
                  }
                  if (showedElmClass) {
                     $container.find(showedElmClass).css('display', 'inline-block');
                  }
               } else {
                  if ($containerElm && !$containerElm.text().trim()) {
                     $containerElm.empty();
                  }
               }
            });
         });
   }

   /**
    * SBIS3.Plan.PlanTileView
    * @class SBIS3.Plan.PlanTileView
    * @extends $ws.proto.CompoundControl
    * @author Полтароков С.П.
    */
   var moduleClass = CompoundControl.extend(/** @lends SBIS3.Plan.PlanTileView.prototype */{
      _dotTplFn: dotTplFn,
      browser: null,
      filterInitialized: false,
      initRec: null,
      firstLoad: true,
      $protected: {
         _options: {
            filterDescr: {},
            filter: {},
            currentDate: new Date()
         }
      },

      init: function () {
         moduleClass.superclass.init.call(this);

         var
            self = this,
            planPointsDialogAction = this.getChildControlByName('planTilePointsDialogAction'),
            browser = this.getChildControlByName('browserView'),
            $browserContainer = this.getContainer(),
            engineBrowser = browser.getParent(),
            buttonAddPoint = this.getChildControlByName('КнопкаПлюсПунктПлитка'),
            buttonAddFolder = this.getChildControlByName('КнопкаДобавитьПапкуВПланРаботПлитка');


         this.browser = browser;
         this.getChildControlByName('ПереключитьНаСписочныйВид').setEnabled(true);

         // обновляем пункты плана по событию
         EventBus.channel('PlanEvent').subscribe('refreshPlanTiles', function () {
            browser.reload();
         });


         this.getChildControlByName('ПлоскийВид').subscribe('onActivated', function () {
            _performTileParamButtomClick.call(self, $(arguments[1].currentTarget), 'ПлоскийСписок', true);
         });
         this.getChildControlByName('ПоСотрудникам').subscribe('onActivated', function () {
            _performTileParamButtomClick.call(self, $(arguments[1].currentTarget), 'ГруппировкаПоСотрудникам', true);
         });


         _initHistoryHint.call(this);
         _initActions.call(this);
         _initCommands.call(this);
         this.initTileView(false);


         function _initCommands() {
            // регистрирубю комманды, присваиваю им обработчики
            CommandDispatcher.declareCommand(this, 'toggleColumns', function (method, elm) {
               method = method || 'addClass';

               var
                  $tileColumnContent = elm.getContainer().closest('.controls-DataGridView__th-content'),
                  $browserContainer = elm.getParent().getContainer();

               if ($tileColumnContent) {
                  var statusId = $tileColumnContent.data('status-id');
                  $tileColumnContent.closest('th')[method]('WorkPlan__tiledView__hidedColumn');
                  if (statusId >= 0) {
                     $browserContainer[method]('tView__hideCol' + statusId);
                  }
               }

               return false;
            });
         }

         /**
          * Инициализация действий
          * @private
          */
         function _initActions() {

            /*обработчик добавления/редактирования на компоненте браузера*/
            engineBrowser.subscribe('onEdit', function (eventObject, metaData) {
               planPointsDialogAction.setProperty('initializingWay', 'remote');

               // создание папки
               if (!metaData.item && self.initRec) {
                  if (metaData && metaData.filter) {
                     metaData.filter['Раздел'] = metaData.filter['ГруппировкаПоПроектам']
                        ? null
                        : metaData.folder_id || metaData.filter['Раздел'] || null; // подставляем Раздел
                     metaData.filter['Раздел@'] = !!metaData.itemType || null;
                     metaData.filter['Раздел$'] = !!metaData.itemType || null;
                     metaData.filter['ДокументОснование'] = self.initRec.get('@Документ');
                     metaData.filter['СвязьДокументовПлан.ПланДата'] = self.initRec.get('Проект.ПланДатаКнц');
                  }
                  planPointsDialogAction.execute(metaData);
               }
            });

            /**
             * кнопка "+" над браузером
             */
            buttonAddPoint.subscribe('onMenuItemActivate', function (event, id) {
               var filter = self.getContext().getValue('filter');
               PlanPoints.addToPlan(id, filter['ГруппировкаПоПроектам'] ? null : (browser.getActiveNodeKey() ? Math.abs(browser.getActiveNodeKey()) : null), browser);
            });

            /**
             * кнопка "+папка" над браузером
             */
            buttonAddFolder.subscribe('onClick', function () {
               var filter = self.getContext().getValue('filter');
               PlanPoints.addToPlan('Папка', filter['ГруппировкаПоПроектам'] ? null : (browser.getActiveNodeKey() ? Math.abs(browser.getActiveNodeKey()) : null), browser);
            });

            // обновляем браузеры если запись не поменялась,
            // но редактировали плановое время или список исполнителей или состояние
            planPointsDialogAction.subscribe('onExecuted', function (event, saved, record) {
               browser.reload();
            });

            //Устанавливаем заглушку для пустого компонента с сообщением и полоской на ширину диалога
            browser.setEmptyHTML('<div class="WorkPlan__tiledView-headerLine"></div>Список работ пока не сформирован');

            //обработчик перевода курсора мыши на другую запись, используется
            //для сокрытия-отображения кнопок по ховеру
            browser.subscribe('onChangeHoveredItem', function (event, data) {
               var
                  actions = browser.getItemsActions(),
                  instances = actions && actions.getItemsInstances(),
                  item = data && data.record,
                  filter = browser.getContext().getValue('filter'),
                  prGrouping = filter && filter['ГруппировкаПоПроектам'],
                  actionsVisible = item && item.get('Раздел@') && !prGrouping,
                  hide_buttons = [],
                  itemId = item && item.getId(),
                  $itemTr = itemId && this.getContainer().find('tr[data-id="' + itemId + '"]'),
                  itemParentDataHash = $itemTr && $itemTr.data('parent-hash'),
                  dlgEnabled = buttonAddPoint.isEnabled();

               instances && dColHelpers.forEach(instances, function (inst, i) {
                  inst.toggle(actionsVisible);
               });

               /**
                * Поиск соседних папок на одном уровне
                * и установка признака сокрытия кнопок перемещения
                * @param tiles
                * @param tileId
                */
               function _findNextSibling(nextDirection, actionCode) {
                  var $nextTr = $itemTr,
                     hasSibling = false;

                  if (hide_buttons.indexOf(actionCode) >= 0) {
                     return;
                  }

                  do {
                     if (nextDirection) {
                        $nextTr = $nextTr.next('tr');
                     } else {
                        $nextTr = $nextTr.prev('tr');
                     }
                     if ($nextTr && $nextTr.filter('.controls-ListView__item-type-node[data-parent-hash=' + itemParentDataHash + ']').length > 0) {
                        hasSibling = true;
                        break;
                     }
                  } while ($nextTr && $nextTr.length > 0);

                  if (!hasSibling) {
                     hide_buttons.push(actionCode);
                  }
               }

               // пользователю без прав запрещаем двигать и удалять пункты
               if (!dlgEnabled || RightsManager.checkAccessRights(['Планы работ']) < 2) {
                  hide_buttons.push('moveUp');
                  hide_buttons.push('moveDown');
                  hide_buttons.push('moveToFolder');
                  hide_buttons.push('delete');
               }

               if (actionsVisible && $itemTr && itemParentDataHash) {
                  // кнопки перемещения вверх / вниз
                  // если уже скрыты - не требуется
                  _findNextSibling(true, 'moveDown');
                  _findNextSibling(false, 'moveUp');
               }

               instances && dColHelpers.forEach(instances, function (inst, i) {
                  if (hide_buttons.indexOf(i) >= 0) {
                     inst.toggle(false);
                  }
               });

            });

            //Событие отрисовка плиточного компонента
            browser.subscribe('onDrawItems', function () {
               //Открываем ранее развернутые плитки
               var tileId = browser.getContext().getValue('hoveredTile');
               if (tileId) {
                  _setHoverTile(false, tileId);
               }
               //Для всех ячеек внутри строк плиточного компонента
               //Ячейка содержит несколько плиток
               //Событие при входе курсора в регион ячейки с группой плиток
               //Событие при выходе курсора из региона ячейки с группой плиток
               $browserContainer.find('.controls-DataGridView__tilesContainerTd')
                  .mouseenter(function (e) {
                     //Если DragDrop - нажата левая кнопка мыши
                     if (e.which == 1) {
                        var $trg = $(this);
                        //Посвечиваем ячеку как активную для бросания, остальные как пассивные
                        $trg
                           .closest('tr')
                           .find('.controls-DataGridView__TiledTd')
                           .addClass('controls-DataGridView__TiledTd-UnactiveDrag');
                        $trg
                           .removeClass('controls-DataGridView__TiledTd-UnactiveDrag')
                           .addClass('controls-DataGridView__TiledTd-ActiveDrag');
                     }
                  })
                  .mouseleave(function (e) {
                     //Гасим все подсветки у ячеек
                     $(this)
                        .closest('tr')
                        .find('.controls-DataGridView__TiledTd')
                        .removeClass('controls-DataGridView__TiledTd-UnactiveDrag')
                        .removeClass('controls-DataGridView__TiledTd-ActiveDrag');
                  });

               //Для всех плиток
               //Событие при входе курсора в регион плитки
               //Событие при выходе курсора из региона плитки
               $browserContainer.find('.WorkPlan__tiledView__tileElement')
                  .mouseenter(function (e) {
                     //Если DragDrop - нажата левая кнопка мыши
                     if (e.which == 1) {
                        //Подсвечиваем плитку как активную для бросания
                        $(this).addClass('WorkPlan__tiledView__DragHihtlighted');
                     }
                  })
                  .mouseleave(function (e) {
                     //Гасим все подсветки у плиток
                     $(this).removeClass('WorkPlan__tiledView__DragHihtlighted');
                     //удалена опция сокрытия при покидании курсором - мешает работе
                     //.removeClass('WorkPlan__tiledView__tileHoveredElement')
                  }).each(function (i, tile) {
                  var tileCode = $(tile).data('tile-code');

                  if (tileCode) {
                     var
                        hasAllComps = false,
                        startTimePicker,
                        endTimePicker,
                        timeTotalPicker;
                     try {
                        startTimePicker = browser.getChildControlByName('ВремяНач' + tileCode);
                        endTimePicker = browser.getChildControlByName('ВремяКнц' + tileCode);
                        timeTotalPicker = browser.getChildControlByName('Время' + tileCode);
                        browser.getChildControlByName('ДатаВремяОтработанное' + tileCode).setDate(new Date());
                        hasAllComps = true;
                     } catch (e) {
                        // pass
                     }

                     if (hasAllComps) {
                        startTimePicker.setDate(null);
                        endTimePicker.setDate(null);
                        timeTotalPicker.setDate(null);
                        /* инициируем контролы */
                        TimePeriodPicker.init(startTimePicker, endTimePicker, timeTotalPicker);
                        /* добавим реакцию на вращение колеса мыши */
                        TimePeriodPicker.addWheelAction(startTimePicker);
                        TimePeriodPicker.addWheelAction(endTimePicker);
                        TimePeriodPicker.addWheelAction(timeTotalPicker);
                     }
                  }
               });

               var
                  dataWorkersIds = this.getContext().getValue('dataWorkersIds'),
                  wrkNames,
                  wrkPhotoHtml,
                  $wrkPhoto;

               //Получение изображений остальных исполнителей помимо основного
               if (dataWorkersIds && dataWorkersIds && dataWorkersIds instanceof Object && Object.keys(dataWorkersIds).length > 0) {
                  $browserContainer.find('.WorkPlan__tiledView__tileElement')
                     .each(function (index, tileContainer) {
                        var
                           $container = $(tileContainer),
                           docId = parseInt($container.data('doc-id'), 10);

                        if (docId && dataWorkersIds[docId] && dataWorkersIds[docId] instanceof Object && dataWorkersIds[docId].wrkIds &&
                           dataWorkersIds[docId].wrkIds.length && dataWorkersIds[docId].wrkIds.length > 1) {
                           var photoHtml = '';

                           dColHelpers.forEach(dataWorkersIds[docId].wrkIds, function (wrkId, i) {
                              if (i > 0 && wrkId) {
                                 wrkPhotoHtml = _getPhoto(wrkId, dataWorkersIds[docId].wrkPhotoIds[i]);
                                 wrkNames = dataWorkersIds[docId].wrkNames;
                                 if (wrkNames && wrkNames[i]) {
                                    $wrkPhoto = $('<i>' + wrkPhotoHtml + '</i>');
                                    $wrkPhoto.children().attr('title', wrkNames[i]);
                                    wrkPhotoHtml = $wrkPhoto.html();
                                 }
                                 photoHtml += wrkPhotoHtml;
                              }
                           });
                           $container
                              .find('.Person-PersonInfo__person-info-bottom')
                              .addClass('Person-PersonInfo__person-info-noTopMargin')
                              .html(photoHtml + (photoHtml ? '' : dataWorkersIds[docId].branchName));
                        }
                     });
               }

            });

            function _setHoverTile($trgClosestTile, linkDocId) {
               if (!$trgClosestTile) {
                  $trgClosestTile = browser.getContainer().find('.WorkPlan__tiledView__tileElement[data-link-doc-id=' + linkDocId + ']');
                  if ($trgClosestTile.length === 0) {
                     return;
                  }
               }
               browser.getContext().setValue('hoveredTile', linkDocId);
               $trgClosestTile.addClass('WorkPlan__tiledView__tileHoveredElement');
               var tileWorksDataGrid = false;

               //Ищем объект таблицы работ с оговоренным name опр формата
               try {
                  tileWorksDataGrid = browser.getChildControlByName('planCheckWorks' + $trgClosestTile.data('id') + '_' + $trgClosestTile.data('status-id'));
               } catch (e) {
                  // pass
               }

               //Если найден объект таблицы работ в подробном виде плитки
               if (tileWorksDataGrid) {
                  //Заполняем таблицу работ плитки
                  tileWorksDataGrid.setDataSource(
                     new SbisService({
                        idProperty: '@Работа',
                        endpoint: 'СвязьДокументовПлан',
                        binding: {
                           query: 'РаботыСотрудниковПоПунктуПланаПлитка'
                        }
                     })
                  );
               }
            }

            //Подписываемся на клик записи по плитке
            browser.subscribe('onItemClick', function (eventObject, id, data, target) {
               var $trg = $(target);

               //Сворачиваем все раскрытые плитки при щелчке по компоненту но вне раскрытых
               if ($trg.closest('.WorkPlan__tiledView__tileElement.WorkPlan__tiledView__tileHoveredElement').length === 0) {
                  $browserContainer
                     .find('.WorkPlan__tiledView__tileElement')
                     .removeClass('WorkPlan__tiledView__tileHoveredElement');
                  browser.getContext().setValue('hoveredTile', null);
               }

               var $trgClosestTile = $trg.closest('.WorkPlan__tiledView__tileElement');

               //Если найден родитель-плитка
               if ($trgClosestTile.length > 0) {
                  eventObject.cancelBubble();
                  var
                     isTileDesc = $trg.hasClass('tiledView__tileDescription'),
                     linkDocId = $trgClosestTile.data('link-doc-id');

                  if ($trg.hasClass('WorkPlan__tiledView__tileElement__moveToPlanButton')) {
                     self.movePointToPlan($trgClosestTile, browser);
                  } else if ($trg.hasClass('tiledView__tileReglamentNameText') || isTileDesc) {
                     var docId = $trgClosestTile.data('doc-id');
                     if (docId && !isTileDesc) {
                        DocOpener.openDocByIDDocument(parseInt(docId, 10), {brows: this}).addCallback(function (res) {
                           if (cInstance.instanceOfModule(res, 'SBIS3.EDO.EdoOpenDialogAction')) {
                              res.subscribe('onExecuted', function (event, saved) {
                                 if (saved === true) {
                                    browser.reload();
                                 } else if (linkDocId) {
                                    renewPlanPoint.call(browser, linkDocId, true, true);
                                 }
                              });
                           }
                        });
                     } else if (linkDocId) {
                        self.openPlanPoint(linkDocId);
                     }
                  } else if ($trg.closest('.tiledView__tileElement__hoveredTimeFormButton').length > 0) {
                     //Для всех плиток
                     //Событие при нажатии на кнопку +Время
                     var
                        $addForm = $trgClosestTile.find('.Tile__Expand-container__addWorkForm'),
                        actionClassName = 'tileElement__planCheck__Works_withAddForm',
                        isHasClass = $trgClosestTile.hasClass(actionClassName);

                     if ($addForm.length) {
                        isHasClass = $trgClosestTile.hasClass(actionClassName);
                        $addForm[isHasClass ? 'hide' : 'show']();
                        $trgClosestTile[isHasClass ? 'removeClass' : 'addClass']();
                     }

                  } else if ($trg.hasClass('expand-TiledView__bottomLine__ReassignButton')) {

                     //Для всех плиток
                     //Событие при щелчке по кнопке Обработка

                     // TODO переписать
                     var
                        record = new Model({
                           adapter: 'adapter.sbis',
                           format: [{
                              name: '@Документ',
                              type: 'integer'
                           }, {
                              name: 'ТипДокумента.ТипДокумента',
                              type: 'string'
                           }],
                           idProperty: '@Документ'
                        });


                     record.set({
                        '@Документ': parseInt($trgClosestTile.data('doc-id'), 10),
                        'ТипДокумента.ТипДокумента': 'Документ'
                     });

                     require(['js!SBIS3.EDO2.DialogPanelOpener'], function (DPO) {
                        new DPO({
                           parent: this,
                           view: this,
                           openedFromReestr: true,
                           isEventsReestr: false,
                           onUpdateModelCallback: function (event, rec, params) {
                              if (params && params.reason && params.reason.length && params.reason.indexOf("passageDone") >= 0) {
                                 browser.reload();
                              }
                           }
                        }).showStack(record);
                     }.bind(self));
                  } else if ($trg.closest('.WorkPlan__tiledView__tileElement-expander').length ||
                     $trg.closest('.WorkPlan__tiledView__tileElement-collapser').length) {
                     //Если хотим раскрыть плитку
                     if (!$trgClosestTile.hasClass('WorkPlan__tiledView__tileHoveredElement')) {
                        _setHoverTile($trgClosestTile, linkDocId);
                     } else {
                        //Сворачиваем плитку
                        $trgClosestTile.removeClass('WorkPlan__tiledView__tileHoveredElement');
                        browser.getContext().setValue('hoveredTile', null);
                     }
                  } else {
                     if (linkDocId) {
                        self.openPlanPoint(linkDocId);
                     }
                  }
                  eventObject.setResult(false);
               }
               //Если хотим раскрыть/свернуть группу плиток
               else if ($trg.hasClass('WorkPlan__tiledView__tilesGroupElement-expander') ||
                  $trg.hasClass('WorkPlan__tiledView__tilesGroupElement-collapser')) {
                  eventObject.cancelBubble();
                  var $tilesGroupContainer = $trg.closest('.WorkPlan__tiledView__tilesGroupElement-container');
                  //Если найден контейнер группы
                  if ($tilesGroupContainer.length > 0) {
                     //Разворачиваем
                     if ($trg.hasClass('WorkPlan__tiledView__tilesGroupElement-expander')) {
                        $tilesGroupContainer.addClass('WorkPlan__tiledView__tilesGroupHoveredElement');
                     } else {
                        //Сворачиваем
                        $tilesGroupContainer.removeClass('WorkPlan__tiledView__tilesGroupHoveredElement');
                     }
                  }
                  eventObject.setResult(false);
               }
            });
         }

         /**
          * Показ всплывашки с историей переноса
          * @private
          */
         function _initHistoryHint() {
            browser.getContainer()
               .on('keydown', '.Tile__Expand-container:visible', function (e) {
                  // по нажатию на табы - переход по времени
                  if (e.which === 9) {
                     e.preventDefault();
                     e.stopPropagation();
                     var
                        td = $(e.target).closest('td'),
                        next = td.length && td.next();
                     next && next.length && next.find('.ws-component').focus();
                  }
               })
               .on('mouseenter', '.WorkPlan__tiledView__tileElement__showPlanHistoryButton', function (event) {
                  event.stopPropagation();

                  var
                     ctx = new cContext(),
                     moveTilesData = browser.getContext().getValue('moveTilesData'),
                     id = parseInt($(this).data('id'), 10),
                     record = moveTilesData && moveTilesData[id] && moveTilesData[id].moveData,
                     fields = {
                        'ПеренесенВ.Документ': 'ПеренесенВ.Документ',
                        'ПеренесенВ.Описание': 'ПеренесенВ.Описание',
                        'ПеренесенВ.ПланДатаНач': 'ПеренесенВ.ПланДатаНач',
                        'ПеренесенВ.ПланДатаКнц': 'ПеренесенВ.ПланДатаКнц',
                        '@Документ': 'ПеренесенИз.Документ',
                        'Описание': 'ПеренесенИз.Описание',
                        'ПланДатаНач': 'ПеренесенИз.ПланДатаНач',
                        'ПланДатаКнц': 'ПеренесенИз.ПланДатаКнц'
                     };

                  if (record) {
                     dColHelpers.forEach(fields, function (field, i) {
                        ctx.setValue(field, record.get(i));
                     });
                     ctx.setValue('КнопкаПереноса', $(this));

                     fcHelpers.showFloatArea({
                        context: ctx,
                        autoHide: true,
                        template: 'js!SBIS3.Plan.PointHistoryHint',
                        opener: browser,
                        target: $(this),
                        offset: {
                           x: -10,
                           y: -10
                        },
                        direction: 'bottom',
                        border: false,
                        animation: 'fade',
                        name: 'История переноса пункта' + randomHelpers.createGUID()
                     });
                  }
               });
         }

         /**
          * Поиск индекса первого элемента с подходящим свойством id
          * @param tiles
          * @param tileId
          * @returns {number} Если не найден возвр. -1
          * @private
          */
         function _getElmIndexById(tiles, tileId) {
            return tiles.map(function (it) {
               return it['id'];
            }).indexOf(tileId);
         }

         /**
          * Переход на плитку
          * @param $trg
          * @param paramName
          * @param invertView
          */
         function _performTileParamButtomClick($trg, paramName, invertView) {
            var filter = self.getContext().getValue('filter');

            if (filter && filter instanceof Object && Object.keys(filter).length > 0) {

               var prGrouping = filter[paramName] = !filter[paramName];

               $trg
                  .toggleClass('icon-disabled', invertView ? prGrouping : !prGrouping)
                  .toggleClass('icon-primary', invertView ? !prGrouping : prGrouping);

               if (paramName.indexOf('ПлоскийСписок') === 0) {
                  $trg.attr('title', $trg.hasClass('icon-primary') ? 'Отобразить плоским списком' : 'Сгруппировать по папкам/проектам');
               }
               self.getContext().setValue('filter', filter);
               browser.reload();
            }
         }


         /**
          * Получить фотографию
          * @param personId
          * @param photoId
          * @returns {string}
          */
         function _getPhoto(personId, photoId) {
            var data = {
                  'data': {
                     'personId': personId,
                     'photoId': photoId
                  },
                  'settings': {
                     'size': 16,
                     'showMiniCard': true,
                     'miniCardShowMode': 'hover'
                  }
               },
               ph = (new Photo(data));
            ph.refresh();
            return ph.getContainer()[0].outerHTML;
         }
      },


      /**
       * Валидация DatePicker
       * Если нет ничего в инпуте - то ошибка
       *
       * @returns {boolean}
       */
      validateDatePicker: function () {
         return this.getText() !== '';
      },

      /**
       * Валидация SBIS3.Staff.Choice
       * Если нет выбранных значений в массиве - то ошибка
       *
       * @returns {boolean}
       */
      validateStaffChoice: function () {
         return (this.getSelectedKeys()).length > 0;
      },

      /**
       * вызов диалога редактирования папки
       * @param event
       */
      browserItemEdit: function (event) {
         var
            browser = this.getParent(),
            dlg = browser.getTopParent(),
            dlgEnabled = dlg.getChildControlByName('КнопкаПлюсПунктПлитка').isEnabled(),
            planPointsDialogAction = dlg.getChildControlByName('planTilePointsDialogAction'),
            record = browser.getHoveredItem().record,
            metaData = {
               hierField: "Раздел",
               id: record.getId(),
               itemType: true,
               item: record,
               nodeProperty: "Раздел@",
               parentProperty: "Раздел"
            };

         planPointsDialogAction.setProperty('initializingWay', 'remote');

         // если редактирование
         if (metaData.item) {
            metaData.item.acceptChanges();

            // редактирование узла
            if (metaData.item.get('Раздел@')) {
               metaData.item.set('РП.РазрешеноРедактировать', dlgEnabled);
               metaData.item.acceptChanges();
               planPointsDialogAction.execute(metaData);
            }
         }
      },

      /**
       * метод, открывающий диалог пункта плана по ид пункта плана
       * @param pointId
       */
      openPlanPoint: function (pointId) {
         var
            browser = this.browser,
            planPointsDialogAction = this.getChildControlByName('planTilePointsDialogAction'),
            planPoints = browser.getContext().getValue('planPoints'),
            metaData = {
               hierField: "Раздел",
               id: null,
               itemType: null,
               nodeProperty: "Раздел@",
               parentProperty: "Раздел"
            },
            dlgEnabled = this.getChildControlByName('КнопкаПлюсПунктПлитка').isEnabled();

         planPointsDialogAction.setProperty('initializingWay', 'remote');

         if (planPoints && planPoints[pointId]) {
            metaData.id = planPoints[pointId].id;
            if (dlgEnabled) {
               browser.getParent().getParent()._notify('onBeforeSignificantChange').addCallback(function (answer) {
                  if (answer) {
                     planPointsDialogAction.execute(metaData);
                  }
               });
            } else {
               planPointsDialogAction.execute(metaData);
            }
         }
      },

      /**
       * основная процедура инициализации плиточного компонента, возможен режим без загрузки данных
       * @param loadData
       */
      initTileView: function (loadData) {
         var
            self = this,
            dlg = self.getTopParent(),
            //родитель и парамерты инициализации от него
            PlanFormController = dlg.hasChildControlByName('PlanFormController')
               ? dlg.getChildControlByName('PlanFormController')
               : dlg,
            record = PlanFormController.getRecord();

         if (record) {
            self.initRec = record;
            self.initTileViewFromRec(record, loadData);
         } else {
            PlanFormController.subscribe('onReadModel', function (event, record) {
               self.initRec = record;
               self.initTileViewFromRec(record, loadData); // открыли в отдельной вкладке
            });
         }
      },

      /**
       * функция инициализации плиточного компонента на основании переданных
       * от родителя данных (уже извлеченных), возможен режим без загрузки данных
       * @param record
       * @param loadData
       */
      initTileViewFromRec: function (record, loadData) {
         var self = this;
         //Берем наборы фаз для столбцов из настроек
         UserConfig.getParam('userSettingTilePlan').addCallback(function (params) {
            var
               // парсю то что пришло из конфигов
               dataSettings = params && JSON.parse(params),
               // данные для фильтра
               dataForSend = {},
               // идентификатор
               splitId = null,
               // тип из юзерконфигов
               type = null,
               // уид, для отправки
               uuid = null,
               // тип фаз
               typeCompare = null,
               // соответствия заголовков фаз
               comparesDataKeys = {
                  'I': 'ФазыЗапланировано',
                  'II': 'ФазыВыполнения',
                  'III': 'ФазыСборки',
                  'IV': 'ФазыПроверки',
                  'V': 'ФазыВыполнено'
               };

            dataSettings = dataSettings ? dataSettings : [];
            // фильтрую, убирая разделы, оставляя только листья
            dataSettings = dataSettings.length && dataSettings.filter(function (item) {
                  return item['Раздел@'] === null;
               });


            // набиваю данные по столбцам
            dataSettings && dataSettings.forEach(function (item) {
               splitId = item['id'].split('|');
               var lastIndex = splitId.length - 1;
               type = splitId[lastIndex];
               uuid = splitId[0];
               typeCompare = comparesDataKeys[type];

               // если нет еще этого раздела - то создаем
               if (!dataForSend[typeCompare]) {
                  dataForSend[typeCompare] = [];
               }
               dataForSend[typeCompare].push(uuid);
            });

            // пока захардкочено на БЛ
            _processingSettings(dataForSend);

         });

         /**
          * Обработка настроек
          * @param params
          * @returns {boolean}
          */
         function _processingSettings(params) {

            _initFilter.call(self, record, params);

            self.filterInitialized = true;

            if (loadData) {
               self.initBrowserDataSource();
            }
         }

         /**
          * Получить фазы
          * @param settingsRecord
          * @param phaseGroupName
          * @returns {Array}
          */
         function _getAndCheckPhases(settingsRecord, phaseGroupName) {
            var phases = [];

            try {
               phases = JSON.parse(settingsRecord.get("Значение"));
            } catch (e) {
               // pass
            }

            if (!phases || phases.length === 0) {
               phases = [];
               UserConfig.setParam(phaseGroupName, JSON.stringify([]));
            }

            return phases;
         }

         /**
          * Получить id фаз
          * @param phases
          * @returns {Array}
          */
         function _getPhasesIds(phases) {
            return phases.map(function (phase) {
               return phase.id;
            });
         }

         /**
          * Инициализация фильтра для метода БЛ в контексте модуля для плиточного компонента
          * @param record
          * @param params
          * @private
          */
         function _initFilter(record, params) {
            var
               date1 = record.get('Проект.ПланДатаНач'),
               date2 = record.get('Проект.ПланДатаКнц'),
               prevFilter = this.browser.getContext().getValue('filter'),
               filter = cMerge({
                  'ИдПланаРабот': record.get('@Документ'),
                  'ФильтрПланДатаНач': date1 ? date1.toSQL() : null,
                  'ФильтрПланДатаКнц': date2 ? date2.toSQL() : null,
                  'СостояниеПунктов': -1,
                  'ФильтрИсполнитель': (prevFilter && prevFilter['ФильтрИсполнитель']) || null,
                  'ФильтрРегламент': (prevFilter && prevFilter['ФильтрРегламент']) || null,
                  'ПлоскийСписок': false,
                  'ГруппировкаПоПроектам': (prevFilter && prevFilter['ГруппировкаПоПроектам']) || false,
                  'ГруппировкаПоСотрудникам': false,

                  'ФазыПроверки': [],
                  'ФазыСборки': [],
                  'ФазыВыполнения': [],


                  'userID': UserInfo.get("ЧастноеЛицо"),
                  'Принадлежность': (prevFilter && prevFilter['Принадлежность']) || 0
               }, params),

               filterDescr = {
                  'ГруппировкаПоПроектам': 'По папкам',
                  'ФильтрТипЗадачПодпись': 'Все',
                  'Принадлежность': 'Я исполнитель'
               },

               ctx = this.getContext(),
               ctxBrowser = this.browser.getContext();

            ctxBrowser.setValue('filter', filter);
            ctxBrowser.setValue('filterDescr', filterDescr);
            ctx.setValue('filter', filter);
            ctx.setValue('filterDescr', filterDescr);
         }
      },

      /**
       * загрузка данных в компонент в зависимости от состояния его инициализации
       */
      loadBrowserData: function () {
         if (this.filterInitialized) {
            if (this.firstLoad) {
               this.initBrowserDataSource();
            } else {
               this.browser.reload();
            }
            this.firstLoad = false;
         } else {
            if (this.initRec) {
               this.initTileViewFromRec(this.initRec, true);
            } else {
               this.initTileView(true);
            }
         }
      },

      /**
       * функция непосредственно создания объекта источника данных
       * и подгрузки из него в плиточный компонент безо всяких условий
       */
      initBrowserDataSource: function () {
         // инициализация источника данных БЛ
         var
            dataSource = new SbisService({
               idProperty: '@СвязьДокументов',
               orderProperty: 'ПорНомер',
               endpoint: 'СвязьДокументовПлан',
               binding: {
                  query: 'СписокДляПлановРаботПлиткаПитон'
               },
               model: PlanTileViewModel
            });

         this.browser.setDataSource(dataSource);
      },

      /**
       * обработчик клика по строке в таблице
       * работ в развернутой плитке
       */
      onWorksItemClick: function (eventObject, id, data, target) {
         var
            worksBrowser = this,
            tileBrowser = worksBrowser.getParent().getParent();

         EventCardOpener({
            opener: tileBrowser,
            person: data.get('ЧастноеЛицо'),
            workId: data.get('@Работа'),
            modal: true,
            autoHide: false,
            handlerOnAfterClose: function () {
               worksBrowser.reload();
            },
            fitWindow: true
         });
      },

      /**
       * обработчик для сокрытия столбца
       */
      hideTileViewColumn: function (event) {
         this.sendCommand('toggleColumns', 'addClass', this);
      },

      /**
       * обработчик для отображения столбца
       */
      showTileViewColumn: function (event) {
         this.sendCommand('toggleColumns', 'removeClass', this);
      },

      /**
       * обработчик события смены исполнителя
       */
      onWorkStaffChoice: function (eventObject) {
         var
            $trgClosestTile = this.getContainer().closest('.WorkPlan__tiledView__tileElement'),
            parent = this.getParent(),
            selIds = this.getSelectedKeys(),
            tileCode = $trgClosestTile.data('tile-code'),
            self = this,
            userCalendars = parent.getContext().getValue('userCalendars');

         if (!userCalendars) {
            userCalendars = {};
         }

         if ($trgClosestTile.length && !$trgClosestTile.hasClass('WorkPlan__tiledView__tileHoveredElement')) {
            return;
         }

         //Получаем ид календаря пользователя по которому добавляется работа
         if (selIds.length > 0 && selIds[0] && $trgClosestTile.length > 0) {
            var
               dataSourceWorks = new SbisService({
                  endpoint: 'Работа',
                  firstLoad: false,
                  binding: {
                     query: 'СписокРаботСотрудникаРабочееВремя'
                  }
               });

            dataSourceWorks
               .query((new Query())
                  .where({
                     'ВсеСотрудники': true,
                     'Дата': new Date(),
                     'Документ': parseInt($trgClosestTile.data('doc-id'), 10),
                     'Исполнитель': selIds[0]
                  }))
               .addCallback(
                  function (result) {

                     var
                        md = new Model({
                           rawData: result.getRawData().r,
                           adapter: 'adapter.sbis'
                        }),
                        hasAllComps = false,
                        addBtn,
                        endTimeControl,
                        startTimeControl,
                        personControls;

                     userCalendars[tileCode] = md.get("КалендарьПользователя");
                     parent.getContext().setValue('userCalendars', userCalendars);

                     try {
                        addBtn = parent.getChildControlByName('КнопкаДобавить' + tileCode);
                        endTimeControl = parent.getChildControlByName('ВремяКнц' + tileCode);
                        startTimeControl = parent.getChildControlByName('ВремяНач' + tileCode);
                        personControls = parent.getChildControlByName('ЧастноеЛицо' + tileCode);
                        hasAllComps = true;
                     } catch (e) {
                        // pass
                     }

                     if (hasAllComps) {
                        addBtn.setEnabled(false);
                        if (!md.get("ПроверкаПравНаРедактирование")) {
                           /*Если прав на редактирование мероприятий нет - то для выбранного сотрудника запрещаем создание отметок времени */
                           dFCHelpers.alert('У Вас нет прав для создания отметок времени для выбранного сотрудника').addBoth(function () {
                              personControls.setSelectedKeys([]);
                           });
                        }
                        else {
                           addBtn.setEnabled(true);
                           if (!endTimeControl.getDate()) {
                              startTimeControl.setDate(md.get("ВремяНач").toServerTime());
                              startTimeControl.setActive(true);
                           }
                        }
                     }
                  }
               );
         } else {
            userCalendars[tileCode] = null;
            parent.getContext().setValue('userCalendars', userCalendars);
         }
      },

      /**
       * обработчик для кнопки открытия задачи
       */
      openTaskDlg: function (eventObject) {
         var
            $trgClosestTile = this.getContainer().closest('.WorkPlan__tiledView__tileElement'),
            browser = this.getParent();

         eventObject.cancelBubble();

         //Если найден родитель-плитка
         if ($trgClosestTile.length > 0) {
            var
               docId = $trgClosestTile.data('doc-id'),
               linkDocId = $trgClosestTile.data('link-doc-id');

            DocOpener
               .openDocByIDDocument(parseInt(docId, 10), {
                  brows: browser
               })
               .addCallback(function (res) {
                  if (cInstance.instanceOfModule(res, 'SBIS3.EDO.EdoOpenDialogAction')) {
                     res.subscribe('onExecuted', function (event, saved) {
                        if (saved === true) {
                           browser.reload();
                        } else if (linkDocId) {
                           renewPlanPoint.call(browser, linkDocId, true, true);
                        }
                     });
                  }
               });
         }
      },

      /**
       * обработчик переключения к списочному виду плана работ
       */
      switchToListPlanView: function () {
         var
            parentCmp = this.getParent().getParent(),
            listCmp = parentCmp.getParent().getChildControlByName('PlanPoints1');

         parentCmp.getContainer().hide();
         listCmp.show();
         listCmp.getContainer().show();
         listCmp.getChildControlByName('browserView').reload();
         UserConfig.setParam('ПунктыПланаПлиткой', 'false');
      },

      /**
       * обработчик для кнопки переноса пункта плана
       */
      movePointToPlan: function ($trgClosestTile, browser) {
         if ($trgClosestTile && browser) {
            var
               docId = parseInt($trgClosestTile.data('doc-id'), 10) || parseInt($trgClosestTile.data('link-doc-id'), 10),
               baseDocId = parseInt($trgClosestTile.data('base-doc-id')),
               pointId = parseInt($trgClosestTile.data('id')),
               selector_action = new SelectorAction({
                  mode: 'floatArea',
                  parent: this.getParent(),
                  template: 'js!SBIS3.Plan.PlanSelector',
                  handlers: {
                     onExecuted: function (event, meta, result) {
                        if (result && result.getCount && result.getCount() > 0) {
                           var toPlanRecord = result.at(0);

                           // если документ уже включён, удалим его из плана
                           if (toPlanRecord.get('ВключенДокумент')) {
                              (new SbisService({
                                 endpoint: 'ПланРабот'
                              }))
                                 .call('ИсключитьИзПлана', {
                                    'ПланРабот': toPlanRecord.getId(),
                                    'Документ': docId
                                 });
                           }

                           fcHelpers.toggleIndicator(true);

                           //Запрос на перенос пункта в дрцгой план
                           (new SbisService({
                              endpoint: 'ПланРабот'
                           }))
                              .call('ПеренестиПунктПлана', {
                                 'ИдПункта': parseInt(pointId, 10),
                                 'ИдНовогоПлана': parseInt(toPlanRecord.getId(), 10)
                              })
                              .addCallback(
                                 function () {

                                    fcHelpers.toggleIndicator(false);

                                    browser.reload();
                                    InformationPopupManager.showNotification({
                                       caption: 'Пункт перенесён.',
                                       icon: 'icon-24 icon-Yes icon-done',
                                       status: 'success'
                                    });

                                 }).addErrback(function (error) {
                              fcHelpers.toggleIndicator(false);
                              InformationPopupManager.showMessageDialog({
                                 status: 'error',
                                 message: error.message
                              });
                           });
                        }
                     }
                  }
               });

            if (docId && baseDocId && pointId) {
               //Вызов диалога выбора плана для переноса
               selector_action.execute({
                  multiselect: false,
                  selectionType: 'leaf',
                  componentOptions: {
                     'ВключенДокумент': docId,
                     'ПереносИзПлана': baseDocId,
                     'ПереносПункта': true
                  }
               });
            }

         }
      },

      //обработчик события загрузки списка работ
      onWorksDataLoad: function (event, dataSet) {
         var
            $trgClosestTile = this.getContainer().closest('.WorkPlan__tiledView__tileElement'),
            parent = this.getParent().getParent();
         //Если найден родитель-плитка
         if ($trgClosestTile.length > 0) {
            var
               tileCode = $trgClosestTile.data('tile-code'),
               personControls,
               tileType = $trgClosestTile.data('tile-type');

            try {
               personControls = parent.getChildControlByName('ЧастноеЛицо' + tileCode);
            } catch (e) {
               //pass
            }

            var selPersIds = personControls && personControls.getSelectedKeys();
            if (personControls && selPersIds.length === 0) {
               personControls.setSelectedKeys([UserInfo.get("ЧастноеЛицо")]);
            } else {
               /*Генерируем событие установки
                * с уже установленным ключом*/
               personControls.removeItemsSelectionAll();
               personControls.setSelectedKeys(selPersIds);
            }
         }
      },

      // закрывает диалог добавления времни
      cancelNewWork: function (event) {
         this
            .getContainer().closest('.WorkPlan__tiledView__tileElement')
            .find('.Tile__Expand-container__addWorkForm')
            .hide()
            .end()
            .parent()
            .find('.tileElement__planCheck__Works_withAddForm')
            .removeClass('tileElement__planCheck__Works_withAddForm');
      },

      // Добавить новую запись в таблицу Работа
      addNewWork: function (event) {
         var
            $trgClosestTile = this.getContainer().closest('.WorkPlan__tiledView__tileElement'),
            parentCmp = this.getParent(),
            commentCtrl,
            startTimeCtrl,
            endTimeCtrl,
            workTimeCtrl,
            personCtrl;

         //Если найден родитель-плитка
         if ($trgClosestTile.length > 0) {
            var
               docId = parseInt($trgClosestTile.data('doc-id'), 10),
               tileCode = $trgClosestTile.data('tile-code'),
               succCmpFound = false,
               insertData = [],
               userCalendars = this.getParent().getContext().getValue('userCalendars'),
               linkDocId = $trgClosestTile.data('link-doc-id');

            if (!userCalendars) {
               userCalendars = {};
            }

            if (docId && tileCode) {

               try {
                  //Формируем данные о новой работе
                  commentCtrl = _getParentChild('СтрокаКомментарий' + tileCode);
                  startTimeCtrl = _getParentChild('ВремяНач' + tileCode);
                  endTimeCtrl = _getParentChild('ВремяКнц' + tileCode);
                  workTimeCtrl = _getParentChild('Время' + tileCode);
                  personCtrl = _getParentChild('ЧастноеЛицо' + tileCode);
                  dateWork = _getParentChild('ДатаВремяОтработанное' + tileCode);

                  insertData = {
                     '@Работа': null,
                     'Дата': dateWork.getDate(),
                     'ВремяНач': startTimeCtrl.getDate(),
                     'ВремяКнц': endTimeCtrl.getDate(),
                     'Время': workTimeCtrl.getDate(),
                     'Примечание': commentCtrl.getValue(),
                     'ЧастноеЛицо': personCtrl.getSelectedKeys()[0],
                     'КалендарьПользователя': userCalendars[tileCode],
                     'Автор': UserInfo.get("ЧастноеЛицо"),
                     'Документ': docId,
                     'Метка': 'Я'
                  };
                  succCmpFound = insertData['КалендарьПользователя'];
               } catch (e) {
                  // pass
               }

               var isValid = personCtrl && personCtrl.validate() && dateWork && dateWork.validate();
               //Если все контролы найдены и данные из них сформированы
               if (isValid && succCmpFound) {

                  // TODO переписать
                  var
                     rec = new Record({
                        format: [{
                           name: '@Работа',
                           type: 'integer'
                        }, {
                           name: 'Дата',
                           type: 'date'
                        }, {
                           name: 'ВремяНач',
                           type: 'time'
                        }, {
                           name: 'ВремяКнц',
                           type: 'time'
                        }, {
                           name: 'Время',
                           type: 'time'
                        }, {
                           name: 'Примечание',
                           type: 'string'
                        }, {
                           name: 'ЧастноеЛицо',
                           type: 'integer'
                        }, {
                           name: 'КалендарьПользователя',
                           type: 'integer'
                        }, {
                           name: 'Автор',
                           type: 'integer'
                        }, {
                           name: 'Документ',
                           type: 'integer'
                        }, {
                           name: 'Метка',
                           type: 'string'
                        }],
                        adapter: 'adapter.sbis'
                     });

                  rec.set(insertData);
                  _addTimeStringFields(rec, rec.get('Дата'), rec.get('ВремяНач'), rec.get('ВремяКнц'));

                  new SbisService({
                     endpoint: 'Работа'
                  })
                     .call('Записать', {'Запись': rec})
                     .addCallback(function (id) {
                        var
                           $workTable = _getParentChild('planCheckWorks' + tileCode),
                           browser = $workTable.getParent().getParent();

                        $workTable.getContainer()
                           .closest('.Tile__Expand-container')
                           .find('.Tile__Expand-container__addWorkForm')
                           .hide();

                        commentCtrl.setValue('');
                        startTimeCtrl.setDate(null);
                        endTimeCtrl.setDate(null);
                        workTimeCtrl.setDate(null);

                        $workTable.reload();

                        $trgClosestTile.removeClass('tileElement__planCheck__Works_withAddForm');
                        if (linkDocId) {
                           renewPlanPoint.call(browser, linkDocId, true, false);
                        }
                     })
                     .addErrback(function (error) {
                        InformationPopupManager.showMessageDialog({
                           status: 'error',
                           message: error.message
                        });
                     });
               } else {
                  if (isValid) {
                     InformationPopupManager.showMessageDialog({
                        status: 'error',
                        message: 'Не указано ФИО сотрудника'
                     });
                  }
               }

            } else {
               InformationPopupManager.showMessageDialog({
                  status: 'error',
                  message: 'Ошибка получения данных!'
               });
            }
         }


         /**
          * функция получения объектов соседних контролов по имени
          * @param childName
          * @returns {*}
          */
         function _getParentChild(childName) {
            return parentCmp.getChildControlByName(childName);
         }

         /**
          * Добавление дополнительных полей защиты
          * @param rec
          * @param workDate
          * @param timeStartValue
          * @param timeEndValue
          */
         function _addTimeStringFields(rec, workDate, timeStartValue, timeEndValue) {
            if (!rec.has('DataString')) {  // защита от повторного добавления при копировании
               rec.addField({
                  name: 'DataString',
                  type: 'string'
               });
            }
            if (!rec.has('TimeStartString')) {  // защита от повторного добавления при копировании
               rec.addField({
                  name: 'TimeStartString',
                  type: 'string'
               });
            }
            if (!rec.has('TimeEndString')) {  // защита от повторного добавления при копировании
               rec.addField({
                  name: 'TimeEndString',
                  type: 'string'
               });
            }

            rec.set({
               'DataString': workDate.toSQL(),
               'TimeStartString': timeStartValue ? timeStartValue.toSQL(false).split('.')[0] : null,
               'TimeEndString': timeEndValue ? timeEndValue.toSQL(false).split('.')[0] : null
            });
         }
      },

      /*
       Событие при завершении загрузки данных в плиточный компонент
       */
      onDataLoad: function (eventObject, dataSet) {
         var
            allTilesTasksIds = [],
            dataWorkersIds = {},
            allTiles = [],
            noPrjTilesTasksIds = [],
            tilesStatCounters = {},
            browser = eventObject.getTarget(),
            ctx = browser.getContext(),
            filter = ctx.getValue('filter'),
            planId = filter && filter['ИдПланаРабот'],
            planPoints = {},
            allTilesRecs = {},
            tileGroupsRecs = {},
            planPointsIds = [],
            accessRightsParamsRawData = [],
            pointsTimeResRawData = [],
            tileTasks,
            itemTilesStatCounters,
            tileGroupId,
            planDocsTimeResources = {},
            tilesCurrUser = {};

         browser.setProperty('showHead', true);

         //Получение списка всех записей-плиток
         //вложенных в строки и группы - цикл перебора строк
         dataSet.each(function (tileItem, i) {
            itemTilesStatCounters = tileItem.get('tilesStatCounters');
            tileTasks = tileItem.get('tiles');

            (tileTasks && tileTasks.length > 0) && _getTileTasks(tileTasks);

            itemTilesStatCounters && dColHelpers.forEach(itemTilesStatCounters, function (scItem, k) {
               if (tilesStatCounters[k]) {
                  tilesStatCounters[k] += scItem || 0;
               } else {
                  tilesStatCounters[k] = scItem || 0;
               }
            });

            tileGroupId = tileItem.getId();
            if (tileGroupId) {
               tileGroupsRecs[tileGroupId] = tileItem;
            }
         });

         ctx.setValue('planPoints', planPoints);
         ctx.setValue('allTilesRecs', allTilesRecs);
         ctx.setValue('tileGroupsRecs', tileGroupsRecs);
         ctx.setValue('dataWorkersIds', dataWorkersIds);
         ctx.setValue('tilesStatCounters', tilesStatCounters);
         ctx.setValue('tilesCurrUser', tilesCurrUser);

         if (accessRightsParamsRawData && accessRightsParamsRawData.length) {

            var dataSourcePlanAccessRights = new SbisService({
               endpoint: 'СвязьДокументовПлан',
               firstLoad: false
            });

            // TODO переписать
            dataSourcePlanAccessRights
               .call('ПраваДоступаПлитка', {
                  'ПунктыПлана': new RecordSet({
                     rawData: {
                        _type: 'recordset',
                        d: accessRightsParamsRawData,
                        s: [{
                           n: '@СвязьДокументов',
                           t: 'Число целое'
                        }, {
                           n: 'ДокументОснование.Лицо3',
                           t: 'Число целое'
                        }, {
                           n: 'ДокументОснование.Сотрудник',
                           t: 'Число целое'
                        }, {
                           n: 'СвязьДокументовПлан.Заказчик',
                           t: 'Число целое'
                        }, {
                           n: 'СвязьДокументовПлан.Исполнитель',
                           t: 'Число целое'
                        }, {
                           n: 'ДокументОснование.ЛицоСоздал',
                           t: 'Число целое'
                        }]
                     },
                     adapter: 'adapter.sbis'
                  }),
                  'ИдПлана': planId
               })
               .addCallback(function (dataSet) {
                  var
                     data = dataSet.getAll(),
                     accessData = {},
                     dataLinkId;

                  data && data.each(function (dataItem, i) {
                     dataLinkId = dataItem.get('@СвязьДокументов');
                     if (dataLinkId) {
                        accessData[dataLinkId] = dataItem;
                     }
                  });

                  _getMovePlanInfo(accessData);
               });

         }

         ctx.setValue('planDocsTimeResources', {});
         pointsTimeRequest(ctx, pointsTimeResRawData);

         if (noPrjTilesTasksIds && noPrjTilesTasksIds.length > 0) {
            _getDocCountersData({
               methodName: 'КоличествоСообщенийПлитка',
               docIds: allTilesTasksIds,
               docIdName: 'ИдО',
               valName: 'КоличествоСообщений',
               valContainerClass: '.WorkPlan__tiledView__tileElement__messagesCount .controls-Button__text',
               showedElmClass: null,
               setTitle: false,
               tilesArray: allTiles
            });
            _getDocCountersData({
               methodName: 'КоличествоПодзадачПлитка',
               docIds: noPrjTilesTasksIds,
               docIdName: '@Документ',
               valName: 'КоличествоПодзадач',
               valContainerClass: '.WorkPlan__tiledView__tileElement__tasksCount .controls-Button__text',
               showedElmClass: '.WorkPlan__tiledView__tileElement__tasksCount',
               setTitle: false,
               tilesArray: allTiles
            });
            _getDocCountersData({
               methodName: 'ВехиПлитка',
               docIds: allTilesTasksIds,
               docIdName: '@Документ',
               valName: 'Веха.Название',
               valContainerClass: '.tiledView__taskMilestoneName',
               showedElmClass: null,
               setTitle: true,
               tilesArray: allTiles
            });
         }

         /**
          * Получить инфорации о перемещении плиток
          * @param accessData
          * @private
          */
         function _getMovePlanInfo(accessData) {
            var dataSourceMovePlanInfo = new SbisService({
               endpoint: 'СвязьДокументовПлан',
               firstLoad: false
            });
            dataSourceMovePlanInfo
               .call('ИнформацияОПереносахПлитка', {
                  "ПунктыПлана": planPointsIds
               })
               .addCallback(function (dataSet) {
                  var
                     data = dataSet.getAll(),
                     moveData = {},
                     moveButtons = {},
                     movePointId,
                     accessDataItem;

                  data && data.each(function (dataItem, i) {
                     movePointId = dataItem.get('@СвязьДокументов');
                     accessDataItem = accessData && accessData[movePointId];
                     if (movePointId && accessDataItem) {
                        moveData[movePointId] = {
                           'moveData': dataItem,
                           'accessData': accessDataItem
                        };
                        moveButtons[movePointId] = _getMoveButtonHTML(dataItem, accessDataItem);
                     }
                  });

                  dColHelpers.forEach(accessData, function (accDataItem, i) {
                     movePointId = accDataItem.get('@СвязьДокументов');
                     if (!moveData[movePointId]) {
                        moveData[movePointId] = {
                           'moveData': null,
                           'accessData': accDataItem
                        };
                        moveButtons[movePointId] = _getMoveButtonHTML(null, accDataItem);
                     }
                  });

                  ctx.setValue('moveTilesButtons', moveButtons);
                  ctx.setValue('moveTilesData', moveData);
               });
         }

         /**
          * Получить HTML переноса информации
          * @param moveData
          * @param accessData
          * @returns {string}
          * @private
          */
         function _getMoveButtonHTML(moveData, accessData) {
            var
               result = '',
               toPlanId = moveData && moveData.get('ПеренесенВ.Документ'),
               fromPlanId = moveData && moveData.get('@Документ'),
               currentUser = cContext.global.getValue('PrivatePerson'),
               hasRights = WorkPlans.getPlanPointPermissions(accessData, currentUser)['Перенос'] && RightsManager.checkAccessRights(['Планы работ']) >= 2,
               classNameToOrFrom = 'WorkPlan__tiledView__tileElement__moveToPlanButton WorkPlan__tiledView__tileElement__showPlanHistoryButton icon-16 icon-Redo2',
               classNameHasRight = 'WorkPlan__tiledView__tileElement__moveToPlanButton WorkPlan__tiledView__tileElement__showPlanWithoutHistoryButton move_point icon-16 icon-Redo2 icon-disabled';


            if (toPlanId || fromPlanId) {
               result = '<div class="' + classNameToOrFrom + ' ' +
                  (toPlanId ? 'icon-error ' : 'icon-primary ') +
                  (hasRights ? 'move_point ' : '') + '" data-id="' + accessData.get('@СвязьДокументов') + '"></div>';
            } else if (hasRights) {
               result = '<div class="+ classNameHasRight +" ' +
                  ' title="Перенести в следующий план работ" ' +
                  'data-id="' + accessData.get('@СвязьДокументов') + '">' +
                  '</div>';
            }
            return result;
         }

         /**
          * Функция извлечения плиток из свойства набора плиток отдельной строки
          * @param tiles
          * @returns {boolean}
          * @private
          */
         function _getTileTasks(tiles) {
            var tileGroupTasks,
               pointResDoc;
            dColHelpers.forEach(tiles, function (tileItem, i) {
                  tileGroupTasks = tileItem['tasks'];
                  if (tileGroupTasks && tileGroupTasks.length > 0 && tileItem.itsGroupTile) {
                     _getTileTasks(tileGroupTasks);
                  } else {
                     allTiles.push(tileItem);
                     allTilesRecs[tileItem.id] = tileItem.record;
                     tilesCurrUser[tileItem.id] = UserInfo.get("ЧастноеЛицо");
                     planPointsIds.push(tileItem.id);
                     accessRightsParamsRawData.push([tileItem.id,
                        tileItem.record.get('ДокументОснование.Лицо3'),
                        tileItem.record.get('ДокументОснование.Сотрудник'),
                        tileItem.record.get('СвязьДокументовПлан.Заказчик'),
                        tileItem.record.get('СвязьДокументовПлан.Исполнитель'),
                        tileItem.record.get('ДокументОснование.ЛицоСоздал')]);

                     pointResDoc = tileItem.record.get('ДокументСледствие');

                     if (pointResDoc) {
                        pointsTimeResRawData.push([tileItem.record.get('РП.ИдИсполнителей'), pointResDoc]);
                     }

                     if (tileItem.docId) {
                        allTilesTasksIds.push(tileItem.docId);
                        dataWorkersIds[tileItem.docId] = {
                           wrkIds: tileItem.wrkIds,
                           wrkNames: tileItem.wrkNames,
                           wrkPhotoIds: tileItem.wrkPhotoIds,
                           branchName: tileItem.branchName
                        };
                     }
                     if (tileItem.docLinkId) {
                        planPoints[tileItem.docLinkId] = tileItem;
                     }
                     if (!(tileItem.reglName.indexOf('Этап') >= 0 || tileItem.reglName.indexOf('Проект') >= 0)) {
                        noPrjTilesTasksIds.push(tileItem['docId']);
                     }
                  }
               }
            );
            return true;
         }

      },

      /*
       Событие при завершении операции DragDrop над плиточным компонентом
       */
      onEndDrag: function (EventObject, dragObject, Event, t4) {
         var
            $eventObj = $(Event.toElement),
            $tileElm = $eventObj.closest('.controls-DataGridView__tilesContainerTd'),
            //элемент плитки на который непосредственно попало бросание
            $dropTile = $eventObj.closest('.WorkPlan__tiledView__tileElement'),
            $tileElmTr = $tileElm.closest('tr'),
            //признак того, что бросили на строку с плитками а не с узлом
            isTileDrop = $tileElm && ($tileElm.length > 0) && $tileElmTr,
            //когда бросаем на элемент строки,
            //внутри которого находится элемент папки
            $innerFolderElm = $eventObj.find('.WorkPlan__tiledView__dragEnd__Folder'),
            //когда бросили на сам элемент папки (ячейку)
            $folderElm = $eventObj.hasClass('WorkPlan__tiledView__dragEnd__Folder') ? $eventObj : ($innerFolderElm.length > 0 ? $innerFolderElm : false),
            //объект верстки с данными узла, есть при любом бросании
            $groupElm = $folderElm || $tileElmTr,
            //извлекаем ид узла (папки) из атрибута
            groupElmId = $groupElm ? $groupElm.data('id') : null,
            $browserContainer = this.getContainer(),
            self = this,
            dragMeta = dragObject.getMeta(),
            //ид перетаскиваемого элемента
            dragId = dragMeta ? dragMeta['data-id'] : null,
            //ид элемента на который бросили
            destId = $dropTile && $dropTile.length ? $dropTile.data('id') : null,
            //статус столбца-источника
            dragSrcStatusId = dragMeta ? dragMeta['data-status-id'] : -1,
            //порядок плитки-источника
            dragOrder = dragMeta ? dragMeta['order-num'] : 0,
            //порядок плитки, на которую бросили
            destOrder = $dropTile && $dropTile.length ? $dropTile.data('order') : null,
            //статус столбца назначения
            destStatusId = $tileElm ? $tileElm.data('status-id') : -1,
            resultedDrop = false,
            //обработка случая для бросания на элемент имени папки
            folderNameParent = $eventObj && $eventObj.context && $eventObj.context.parentNode,
            folderNameParentId = folderNameParent.className.indexOf('WorkPlan__tiledView__dragEnd__Folder') >= 0 && $(folderNameParent).data('id'),
            removeClasses = 'WorkPlan__tiledView__Enable-DragHihtlighted';

         destStatusId = destStatusId && ((destStatusId >= 2) && (destStatusId <= 3) ? -1 : destStatusId);
         //уточняем ид узла (папки) в случае бросания на имя папки
         groupElmId = groupElmId || folderNameParentId;

         //Снимаем пометку всех плиток компонента для отработки подсветки во время DragDrop
         $browserContainer
            .find('.WorkPlan__tiledView__tileElement')
            .removeClass(removeClasses)
            //Снимаем пометку всех ячеек компонента для отработки подсветки во время DragDrop
            .end()
            .find('.controls-DataGridView__tilesContainerTd')
            .removeClass(removeClasses);

         //Функция обобщения значения статуса
         //до 3 состояний
         function generalizeStatus(statusValue) {
            switch (statusValue) {
               case 2:
               case 3:
                  statusValue = 1;
                  break;
               case 4:
                  statusValue = 2;
                  break;
               default:
                  break;
            }
            return statusValue;
         }

         //приводим к модулю отрицательные ид строк с пдитками, формируемые в БЛ
         //на основе реальных ид узлов (папок) родителей - то есть получаем
         //в качестве элемента приземления при кидании на строку с плитками ее
         //папку с реальным ид-шником
         groupElmId = groupElmId && groupElmId != -1 ? Math.abs(groupElmId) : null;
         if (dragMeta.isTileGroup) {
            if (groupElmId) {
               var
                  tileGroupsRecs = self.getContext().getValue('tileGroupsRecs'),
                  tileGroupRec = tileGroupsRecs && dragId ? tileGroupsRecs[dragId] : null;
               resultedDrop = true;
               //Если найдена модель, соответствующая папке
               //и не бросаем на саму себя
               if (tileGroupRec && tileGroupRec.getId() != groupElmId) {
                  //Записываем нового родителя (папку)
                  tileGroupRec.set('Раздел', groupElmId);
               }
            }
         }
         //Если бросили на ячейку внутри одной строки
         else if (isTileDrop && dragMeta['data-group-id'] === $tileElmTr.data('id')) {
            //Показываем параметры источника и приемника
            //которые будут использоваться для передачи БЛ
            //Источник: ид элемента=" + dragObject.getMeta()['data-id'] + ', фаза=' +
            //dragObject.getMeta()['data-status-id'] + ', группа=' + dragObject.getMeta()['data-group-id'] +
            //', Назначение: фаза=' + $tileElm.data('status-id') + ', группа=' +
            //$tileElm.closest('tr').data('id')
            resultedDrop = true;
            if (dragSrcStatusId == 0 && destStatusId == 1) {
               _setPlanPointStatus(dragMeta['data-id'], destStatusId, dragSrcStatusId);
               //Если перетаскиваем внутри ячейки, то есть меняем порядок плиток
            } else if (dragSrcStatusId == destStatusId && dragOrder && destOrder && dragOrder != destOrder && destId) {
               _tileOrderMove(destId, dragId, dragOrder > destOrder ? 'before' : 'after');
            } else {
               dFCHelpers.alert('Разрешено только перемещение из "Запланировано" в "Выполнение"!');
            }
            //Если не бросили внутри одного столбца
            //и найден элемент группы на которую бросили
         } else if (groupElmId) {
            var filter = self.getContext().getValue('filter');
            //Проверка режима группировки по проектам
            //в котором нельзя перетягивать между папками
            if (filter['ГруппировкаПоПроектам']) {
               dFCHelpers.alert('В текущем фильтре поддерживается перемещение только внутри проекта!');
            } else {
               var
                  allTilesRecs = self.getContext().getValue('allTilesRecs'),
                  tileRec = allTilesRecs && dragId ? allTilesRecs[dragId] : null;
               //Если найдена модель, соответствующая плитке
               if (tileRec && tileRec.get('Раздел') !== groupElmId) {
                  //Записываем нового родителя (папку)
                  tileRec.set('Раздел', groupElmId);
                  var recStatus = generalizeStatus(tileRec.get('СвязьДокументовПлан.Состояние'));
                  //Если надо менять статус вместе с папкой
                  if (isTileDrop && destStatusId == 1 && dragSrcStatusId == 0) {
                     recStatus = 1;
                  }
                  tileRec.set('СвязьДокументовПлан.Состояние', recStatus);
                  fcHelpers.toggleIndicator(true);
                  resultedDrop = true;
                  //Сохраняем новое положение плитки (пункта)
                  _saveDocLink(tileRec);
               }
            }
         }
         else {
            //Элемент брошен не на подходящий столбец фазы!
         }

         EventObject.setResult(resultedDrop);

         /**
          * Функция смены порядкового номера двух плиток
          * @param destId
          * @param dragId
          * @param order
          */
         function _tileOrderMove(destId, dragId, order) {
            if (destId && dragId && order) {
               var dataSource = new SbisService({
                  endpoint: 'IndexNumber'
               });

               fcHelpers.toggleIndicator(true);
               dataSource
                  .call('Move', {
                     'IndexNumber': 'ПорНомер',
                     'HierarchyName': 'Раздел',
                     'ObjectName': 'СвязьДокументовПлан',
                     'ObjectId': [dragId],
                     'DestinationId': destId,
                     'Order': order
                  })
                  .addCallback(function (dataSet) {
                     fcHelpers.toggleIndicator(false);
                     self.reload();
                  })
                  .addErrback(function (error) {
                     fcHelpers.toggleIndicator(false);
                     InformationPopupManager.showMessageDialog({
                        status: 'error',
                        message: 'Ошибка операции перемещения!'
                     });
                  });
            }
         }

         /**
          * Функция установки статуса пункта плана если это необходимо
          * @param pointId
          * @param status
          * @param currentStatus
          */
         function _setPlanPointStatus(pointId, status, currentStatus) {
            //Отправка запроса на установку состояния Выполнение Пункта Плана
            status = generalizeStatus(status);
            currentStatus = generalizeStatus(currentStatus);
            if (status != currentStatus) {
               var dataSource = new SbisService({
                  endpoint: 'СвязьДокументовПлан'
               });
               fcHelpers.toggleIndicator(true);

               dataSource
                  .call('ЗаписатьСостояние', {
                     "СвязьДокументов": pointId,
                     "Состояние": status
                  })
                  .addCallback(function (dataSet) {
                     fcHelpers.toggleIndicator(false);
                     self.reload();
                  })
                  .addErrback(function (error) {
                     fcHelpers.toggleIndicator(false);
                     InformationPopupManager.showMessageDialog({
                        status: 'error',
                        message: error.message
                     });
                  });
            }
         }

         /**
          * Функция сохранения записи типа СвязьДокументовПлан
          * @param docLinkRec
          */
         function _saveDocLink(docLinkRec) {
            new SbisService({
               endpoint: 'СвязьДокументовПлан'
            })
               .call('Записать', {'Запись': docLinkRec})
               .addCallback(function (id) {
                  fcHelpers.toggleIndicator(false);
                  self.reload();
               })
               .addErrback(function (error) {
                  fcHelpers.toggleIndicator(false);
                  InformationPopupManager.showMessageDialog({
                     status: 'error',
                     message: error.message
                  });
               });
         }
      },

      /*
       Событие при инициации операции DragDrop над плиточным компонентом
       */
      onBeginDrag: function (EventObject, dragObject, Event) {
         var
            $dragElm = $(Event.toElement),
            $tileElm = $dragElm.closest('.WorkPlan__tiledView__tileElement'),
            itsPlanTile = ($tileElm.length > 0),
            $tileGroupElm = $dragElm.closest('.controls-DataGridView__compound-Td'),
            $browserContainer = this.getContainer(),
            filter = this.getContext().getValue('filter'),
            isTileGroup = $tileGroupElm && ($tileGroupElm.length > 0) && !filter['ГруппировкаПоПроектам'],
            startDrag = (itsPlanTile && $tileElm) || isTileGroup;

         if (startDrag) {
            var ddData = {};
            //Формируем данные для перетягиваемого объекта
            if (isTileGroup) {
               ddData = {
                  'data-id': $tileGroupElm.closest('tr').data('id'),
                  'isTileGroup': true
               };
            } else {
               ddData = {
                  'data-id': $tileElm.data('id'),
                  'data-group-id': $tileElm.data('group-id'),
                  'data-status-id': $tileElm.data('status-id'),
                  'data-sub-group-id': $tileElm.data('sub-group-id'),
                  'order-num': $tileElm.data('order'),
                  'grouped-tile': $tileElm.hasClass('WorkPlan__tiledView__GroupItem-TileElement'),
                  'src-type': 'WorkPlan__tiledView__tileElement',
                  'isTileGroup': false
               };
            }
            // - Помечаем все плитки компонента для отработки подсветки во время DragDrop
            //Не работает событие onDragOver, нужны доп классы
            //Пока что помечаются все плитки, но возможно надо будет помечать
            //только плитки данной строки или ячейки в зависимости от фазы и тд
            // - Помечаем все ячейки компонента для отработки подсветки во время DragDrop
            //Не работает событие onDragOver, нужны доп классы
            //Пока что помечаются все ячейки, но возможно надо будет помечать
            //только ту, с которой началось перетягивание, это зависит от бизнес-логики
            //управления задачами (пунктами плана) в конкретном случае
            $browserContainer.find('.WorkPlan__tiledView__tileElement')
               .add($browserContainer.find('.controls-DataGridView__tilesContainerTd'))
               .addClass('WorkPlan__tiledView__Enable-DragHihtlighted');

            dragObject.setMeta(ddData);
         }

         return EventObject.setResult(startDrag);
      }
   });

   return moduleClass;
}
)
;
