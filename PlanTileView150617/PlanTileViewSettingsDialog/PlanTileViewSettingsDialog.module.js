/**
 * SBIS3.Plan.PlanTileViewSettingsDialog
 * @class SBIS3.Plan.PlanTileViewSettingsDialog
 * @extends $ws.proto.CompoundControl
 * @author Любинский Сергей
 * Настройки для планов работ. Сохраняет в UserConfig в поле userSettingTilePlan
 */

define('js!SBIS3.Plan.PlanTileViewSettingsDialog', [
      'tmpl!SBIS3.Plan.PlanTileViewSettingsDialog',
      'js!SBIS3.CONTROLS.CompoundControl',

      'Core/UserConfig',
      'Core/ClientsGlobalConfig',
      'Core/helpers/fast-control-helpers',
      'Core/CommandDispatcher',
      'Core/core-merge',
      'Core/core-functions',

      'js!WS.Data/Collection/RecordSet',
      'js!WS.Data/Entity/Record',

      'js!SBIS3.CONTROLS.Utils.InformationPopupManager',

      'js!SBIS3.CONTROLS.Button',
      'js!SBIS3.CONTROLS.TreeDataGridView',

      'js!SBIS3.EDO2.RuleList',
      'js!SBIS3.EDO2.PhaseList',

      'css!SBIS3.Plan.PlanTileViewSettingsDialog'

   ], function (dotTplFn, CompoundControl, UserConfig, ClientsGlobalConfig, fcHelpers, CommandDispatcher, cMerge, cFunctions, RecordSet, Record, InformationPopupManager) {

      var
         // сообщения нотификации
         NOTIFICATION_MGS = {
            error: {
               caption: 'Обшибка сохранения настроек плана работ',
               status: 'error'
            },
            success: {
               caption: 'Настройки плана работ обновлены',
               status: 'success'
            }
         },

         // типы документа для выборки регламентов
         DOCUMENT_TYPES = ['СлужЗап'],

         // класс для окна диалога выбора
         CLASS_NAME_OF_DIALOG = 'tile-view-settings-rule-list',

         // заголовок окна добавления регламентов
         TITLE_OF_DIALOG_RULE = 'Выберите регламенты',
         // название компоеннта
         COMPONENT_NAME_DIALOG_RULE = 'js!SBIS3.EDO2.RuleList',


         // заголовок окна добавления регламентов
         TITLE_OF_DIALOG_PHASE = 'Выберите фазы регламентов',
         // имя компонента для выбора фаз
         COMPONENT_NAME_DIALOG_PHASE = 'js!SBIS3.EDO2.PhaseList',

         // захаркоженые столбцы, их идентификаторы
         HARDCODE_RULES_INDEX = ["I", "II", "III", "IV", "V"],
         // заголовки этих столбцов
         HARDCODE_RULES_NAMES = ['Запланировано', 'Выполнение', 'Сборка', 'Проверка', 'Выполнено'],


         // пример того какие рекорды будут
         HARDCODE_RULES_RAW_DATA = {
            'id': null,
            'Название': null,
            'Раздел': null,
            'Раздел@': true
         },

         // название поля для сохранения
         USER_CONFIG_SETTING_PARAM_NAME = "userSettingTilePlan",

         // классы для таблицы выбора регламентов
         CLASSES_ON_RULE_TABLE = [
            'controls-ListView__withoutMarker',
            'controls-TreeDataGridView__hideExpandsOnHiddenNodes',
            'controls-DataGridView__hasSeparator',
            'controls-DataGridView__overflow-ellipsis',
            'controls-DataGridView__sidePadding-24',
            'controls-DragNDropMixin__notDraggable',
            'TreeViewMixincontrols-ListView__hideCheckBoxes-node'
         ].join(' '),


         // описание самого модуля
         moduleClass = CompoundControl.extend(/** @lends SBIS3.Plan.PlanTileViewSettingsDialog.prototype */{
            _dotTplFn: dotTplFn,

            $protected: {
               _options: {
                  cssTreeDataGridView: CLASSES_ON_RULE_TABLE
               },

               // сама таблица
               DataGridView: false,
               // кнопка "сохранить"
               SaveButton: false

            },


            init: function () {
               moduleClass.superclass.init.call(this);

               var
                  DataGridView = this.getChildControlByName('tile-view-settings-body'),
                  SaveButton = this.getChildControlByName('tile-view-settings-header-save');

               // запоминаю контролы в компоненте
               this.DataGridView = DataGridView;
               this.SaveButton = SaveButton;

               // скрываю кнопку сохранения
               this.SaveButton.hide();

               // показываю лоадер
               trobberController.call(this, true);


               // устанавливаю заголовочные айтемы
               DataGridView.setItems(returnHeadParent.call(this));
               // устанавливаю кнопки действий над строкой
               DataGridView.setItemsActions(returnActionItems.call(this));


               // при наведении - для сокрытия/открытия айтемов действий над строркой
               DataGridView.subscribe('onChangeHoveredItem', showTableActionItemsOnType.bind(this));

               // кнопка сорхранения
               SaveButton.subscribe('onActivated', setRuleAndPhase.bind(this));

               // **** Устанавливаю датасурс
               this.setDataSource();

            },


            /**
             * Устанавливаю рекордсет в таблицу
             */
            setDataSource: function () {
               // получаю данные
               getRuleAndPhase.call(this);
               // скрываю лоадер
               trobberController.call(this, false);
            },

            /**
             * Получаю весь список айтемов из таблицы
             */
            getRecordSet: function () {
               return this.DataGridView.getItems();
            },


            /**
             * Устанавливаю айтемы в датагрид
             * @param items {JSON} - айтемы из json для вставки
             */
            setRecordSet: function (items) {
               // сохраняю для сравнения
               this.DataGridView.setItems(items);

            },

            /**
             * Добавляю рекорды в текущий рекордсет
             * @param recordsArray - {Array of records}
             */
            appendRecOnRS: function (recordsArray) {
               // добавляю рекордсекты
               (this.getRecordSet()).append(recordsArray);
            }
         });

      return moduleClass;

      /**
       * Показываем информационное окно об успехе или неудаче
       * @param typeNotification
       */
      function showNotification(typeNotification) {
         InformationPopupManager.showNotification(NOTIFICATION_MGS[typeNotification]);
         trobberController.call(this, false);
      }

      /**
       * Скрываем или показывам лоадер
       * @param isShow
       */
      function trobberController(isShow) {
         fcHelpers.toggleLocalIndicator(this.getContainer(), isShow);
      }


      /**
       * Работа с ЮзерКонфигом. (Оболочка для юзерконфига)
       * @param type - [true - получение данных][false - запись]
       * @param data - данные для сохранения
       * @returns {*} - deferred
       */
      function workingWithUserConfig(type, data) {
         type = type || false;
         return UserConfig[(type ? "get" : "set") + "Param"](USER_CONFIG_SETTING_PARAM_NAME, type ? null : data);
      }

      /**
       * Вывожу данные из юзерконфига
       */
      function getRuleAndPhase() {
         var
            self = this,
            _json = [];

         // достаю дланные из юзерконфиг, преобразую в json и пихаю в таблицу
         workingWithUserConfig(true).addCallback(function (data) {
            _json = data && JSON.parse(data);
            _json && _json.length && self.setRecordSet(_json, true);
            // скрываю лоадер
            trobberController.call(self, false);
         });
      }


      /**
       * Сохранение таблицы
       */
      function setRuleAndPhase() {
         var
            self = this,
            // получаю джейсон  и перевожу в строку
            jsonText = JSON.stringify(getRawFilteredData.call(this));

         // показываю лоадер
         trobberController.call(self, true);

         // если есть текст, то записываю в конфиг
         jsonText && workingWithUserConfig(false, jsonText)
            .addCallback(function () {
               // сообщею что все ок и сохранено
               showNotification.call(self, 'success');
               // крываю кнопку сохранения
               self.SaveButton.hide();
            })
            .addErrback(function () {
               // говорю об ошибке сохранения
               showNotification.call(self, 'error');
            });
      }

      /**
       * Получение сырых данных(JSON) для сохранения
       *    + фильтрация повторяющихся
       * @returns {*|Object}
       */
      function getRawFilteredData() {
         var
            // сырые данные таблицы
            RSData = this.getRecordSet(),
            rawData = RSData ? RSData.getRawData() : false,
            // поля для сравнения повторений
            fieldsCompare = ['Раздел', 'id'],
            // вспомогательные логическиен элементы
            isNotComparedIndex = false,
            isComparedItems = false;

         // чищу от повторений.
         rawData && rawData.forEach(function (itmCompare1, index1) {
            rawData.forEach(function (itmCompare2, index2) {
               // если не переопределенный айтем, элементы для сравнения с разными индексами
               isNotComparedIndex = (index1 !== index2 && itmCompare2 !== false);
               // и он он совпадает с элементом из первого цикла -
               isComparedItems = (compare(itmCompare1, itmCompare2, 0) && compare(itmCompare1, itmCompare2, 1));
               // то переопределяю на false, для фильтрации
               rawData[index2] = ( isNotComparedIndex && isComparedItems ) ? false : rawData[index2];
            });
         });

         // фильтрую
         rawData = rawData && rawData.filter(function (item) {
               return item !== false;
            });

         // возвращаю
         return rawData;

         /**
          * Сравниваю для очисти на совпадение раздела и айди
          * @param item1 - объекты для сравнения
          * @param item2 - объекты для сравнения
          * @param index - индекс
          * @returns {boolean}
          */
         function compare(item1, item2, index) {
            return item1[fieldsCompare[index]] === item2[fieldsCompare[index]];
         }
      }


      /**
       * Тут будет сформирован список кколонок
       * @returns {[*,*]} - возвращает список  заголовчных строк (первый уровень вложенности)
       */
      function returnHeadParent() {
         var
            headParent = [],
            iterationSize = HARDCODE_RULES_INDEX.length,
            iteration = 0;

         // формирую заголовок захардкоденый
         for (iteration; iteration < iterationSize; iteration++) {
            headParent.push(mergeRawData({
               'id': HARDCODE_RULES_INDEX[iteration],
               'Название': HARDCODE_RULES_NAMES[iteration]
            }));
         }

         return headParent;
      }


      /**
       * Обработка выбора регламентов из флоатарем
       * @param self -  конеткст (этот модуль)
       * @param newRulesItem - список рекордов
       * @param headId - идентификатор заголовка
       * @param type - тип того что закрываю
       */
      function onFloatAreaClose(self, newRulesItem, headId, type) {
         var
            // записи которые аппендить
            recordsArray = [],
            //  если регламент - то Раздел@==true иначе null, потому что это конечныый элемент
            isRuleOrPhase = type === 'addRule' ? true : null;

         // Создаю папки для регламентов
         newRulesItem.forEach(function (itemRule) {
            // данные для вставки и мержа с Раздел@
            pushInArr({
               // новый идентификатор. Если поле ИдФазы - то это Фаза, если нет - то это Регламент
               'id': (itemRule.get('ИдРегламентаИдФазы') || itemRule.get('Идентификатор')) + "|" + headId,
               // название - везде название
               'Название': itemRule.get('Название'),
               // раздел == заголовок по на котом нажали +
               'Раздел': headId,
               // поменяется на null, если это фаза
               'Раздел@': true
            });
         });

         // добавляю рекорды в рекордсеты
         if (recordsArray.length) {
            // скрываю флопаатарею
            this.hide();
            // добавляю в рекордсет
            self.appendRecOnRS(recordsArray);
            // кнопка добавить
            self.SaveButton.show();
         }


         /**
          * Функция для добавления новых рекордов в рекордсет
          * @param dataRaw {JSON}- данные для мержа для нового рекорда
          */
         function pushInArr(dataRaw) {
            recordsArray.push(new Record({
               rawData: mergeRawData(cMerge(dataRaw, {
                  'Раздел@': isRuleOrPhase
               }))
            }));
         }
      }

      /**
       * Добавляю фазу в регламент
       * @param $item
       * @param id
       * @param model
       * @param actionName
       */
      function addPhase($item, id, model, actionName) {
         var self = this;
         // показываю окно
         floatAreaHelper.call(this, {
            // темлайт
            template: COMPONENT_NAME_DIALOG_PHASE,
            title: TITLE_OF_DIALOG_PHASE,
            // опции для компонентов внутри
            componentOptions: {
               // регламенты. формирую через разделеитель, по этому делаю сплит
               rule: id.split("|")[0],
               // только листья для выборп
               selectionType: 'leaf',
               // мультивыбор
               multiSelect: true
            },
            // Обработчики
            handlers: {
               onInit: function () {
                  // подписываюсь на закрытие окна
                  CommandDispatcher.declareCommand(this, 'close', function (newRulesItem) {
                     onFloatAreaClose.call(this, self, newRulesItem, id, actionName);
                  });
               }
            }
         })
      }

      /**
       * Добавление регламета для. Контест - moduleClass
       * @param $item
       * @param id
       * @param model
       * @param actionName  - название акшена
       */
      function addRule($item, id, model, actionName) {
         var self = this;

         floatAreaHelper.call(this, {
            template: COMPONENT_NAME_DIALOG_RULE,
            title: TITLE_OF_DIALOG_RULE,
            componentOptions: {
               // мультиселект
               multiSelect: true,
               // по итогу получаю список листьев, если кликнул по папке
               selectChildren: true,
               // типы документов
               docTypes: DOCUMENT_TYPES
            },
            // Обработчики
            handlers: {
               onInit: function () {
                  // подписываюсь на закрытие окна
                  CommandDispatcher.declareCommand(this, 'close', function (newRulesItem) {
                     onFloatAreaClose.call(this, self, newRulesItem, id, actionName);
                  });
               }
            }
         })
      }

      /**
       * Удаляю запись
       * @param $item - строка
       * @param id - идентификатор
       * @param record - запись, котрую надо удалить
       */
      function deleteRecords($item, id, record) {
         var
            recordSet = record.getOwner();

         // получаю родительский рекордсет, и удаляю текущую запись
         recordSet.remove(record);

         // чищу все рекорды что нахоидятся в удаленном разделе
         recordSet.each(function (itemRec) {
            (itemRec.get('Раздел') === record.get('id')) && itemRec.setState(Record.RecordState.DELETED);
         });
         // применяю состония рекордов
         recordSet.acceptChanges();

         // показываю кнопку сохранения
         this.SaveButton.show();
      }

      /**
       * Вызов флоатареи
       * @param options
       */
      function floatAreaHelper(options) {
         // показываю окно
         fcHelpers.showFloatArea(cMerge({
            // темлайт
            template: '',
            //модальность
            modal: false,
            isModal: false,
            isStack: true,
            autoShow: true,
            // стилевое оформление
            className: CLASS_NAME_OF_DIALOG,
            title: '',
            width: 800,
            // таргеты
            targetPart: false,
            opener: this,
            // опции для компонентов внутри
            componentOptions: {},
            // Обработчики
            handlers: {}
         }, options));
      }

      /**
       * Возвращаю айтемы для ховера по строке на таблице
       * @returns {[*,*]}
       */
      function returnActionItems() {
         return [{
            name: 'delete',
            icon: 'sprite:icon-16 icon-Erase icon-error',
            caption: 'Удалить',
            isMainAction: true,
            onActivated: deleteRecords.bind(this)
         }, {
            name: 'addRule',
            icon: 'sprite:icon-16 icon-AddButton icon-primary',
            caption: 'Добавить  регламент',
            isMainAction: true,
            onActivated: addRule.bind(this)
         }, {
            name: 'addPhase',
            icon: 'sprite:icon-16 icon-AddButton icon-primary',
            caption: 'Добавить фазу',
            isMainAction: true,
            onActivated: addPhase.bind(this)
         }];
      }


      /**
       * Мержит сырые данные для нового рекорда, с тем что мне надо
       * @param obj
       * @returns {*}
       */
      function mergeRawData(obj) {
         return cMerge(cFunctions.clone(HARDCODE_RULES_RAW_DATA), obj);
      }

      /**
       * Обработка айтемов для редактирования строк
       * @param event
       * @param obj
       */
      function showTableActionItemsOnType(event, obj) {
         var
            actions = this.DataGridView.getItemsActions(),
            instances = actions.getItemsInstances();

         // удаляю  все кнопки
         instances['addPhase'].hide();
         instances['addRule'].hide();
         instances['delete'].hide();

         // если ключа нет - то это листок.
         if (obj.record && obj.record.get('Раздел@') === null) {
            // показываю "удалить"
            instances['delete'].show();
            return false;
         }

         if (!obj.record) {
            return false;
         }

         // если есть раздел такой, то это главынй индекс
         if ((HARDCODE_RULES_INDEX.indexOf(obj.key) + 1 )) {
            // показываю "добавить регламент"
            instances['addRule'].show();
         } else {
            // если есть раздел такой, то это главынй индекс
            if ((HARDCODE_RULES_INDEX.indexOf(obj.record.get('Раздел')) + 1 )) {
               // показываю "добавить фазу"
               instances['addPhase'].show();
               // удалить
               instances['delete'].show();
            }
         }
      }
   }
);
