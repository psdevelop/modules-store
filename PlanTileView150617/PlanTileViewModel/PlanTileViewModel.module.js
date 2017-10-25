define('js!SBIS3.Plan.PlanTileViewModel',
   [
      'Core/helpers/string-helpers',
      'Deprecated/helpers/collection-helpers',
      'js!WS.Data/Entity/Model'// Подключаем класс, который используется для расширения модели
   ],
   function (strHelpers, dColHelpers, Model) {
      return Model.extend({ // Производим расширение модели
         _moduleName: 'SBIS3.Plan.PlanTileViewModel',
         $protected: {
            _options: {
               properties: {
                  'id': {
                     get: function () {
                        return this.get("@СвязьДокументов");
                     }
                  },
                  'name': {
                     get: function () {
                        return strHelpers.escapeHtml(strHelpers.escapeTagsFromStr(this.get("Примечание")));
                     }
                  },
                  'parent': {
                     get: function () {
                        return this.get("Раздел");
                     }
                  },
                  'parent@': {
                     get: function () {
                        return this.get("Раздел@") ? true : null;
                     }
                  },
                  'Проект.ПланДатаНач': {
                     get: function (val) {
                        return val ?
                           (this.get("Проект.ПланДатаКнц") ? '' : 'c ') + val.strftime('%d.%m.%y') : null;
                     }
                  },
                  'Проект.ПланДатаКнц': {
                     get: function (val) {
                        return val ?
                           (this.get("Проект.ПланДатаНач") ? '' : 'до ') + val.strftime('%d.%m.%y') : null;
                     }
                  },
                  //TODO набор данных в виде датасет, который
                  //не хочет но должен разбираться шаблонизатором
                  /*'tiles_rs': {
                   get: function(val) {
                   return this.get('tiles')
                   }
                   },*/
                  //массив счетчиков задач по статусам
                  'tilesStatCounters': {
                     get: function () {
                        var tilesStatusesCounters = {},
                           tiles = this.get("tilesData");
                        dColHelpers.forEach(tiles, function (tileItem, i) {
                           var taskCnt = tileItem.taskCnt,
                              status = tileItem.status,
                              statCounterIncrement = 1;
                           if (taskCnt && taskCnt > 0) {
                              statCounterIncrement = taskCnt;
                           }
                           if (tilesStatusesCounters[status]) {
                              tilesStatusesCounters[status] += statCounterIncrement;
                           } else {
                              tilesStatusesCounters[status] = statCounterIncrement;
                           }
                        });
                        return tilesStatusesCounters;
                     }
                  },
                  //набор плиток для строки, перепаковывамый в массив
                  //см комментарий к полю выше
                  'tilesData': {
                     get: function () {
                        /**
                         * подсветка части строкового поля, которая совпадает со строкой поиска
                         * */
                        var val = this.get('tiles');

                        function highlightText(text, searchText) {
                           if (!searchText || !text) {
                              return text;
                           }
                           var pos = text.toLowerCase().indexOf(searchText.toLowerCase());
                           if (pos < 0) {
                              return text;
                           }
                           var
                              len = searchText.length,
                              str1 = text.substring(0, pos),
                              str2 = text.substring(pos, pos + len),
                              str3 = text.substring(pos + len);
                           return str1 + '<span class="ws-browser-highlight tiledView__tileElement__hightlightText">' + str2 + '</span>' + str3;
                        }

                        /**

                         * Рекурсивная функция обработки данных и перепаковки
                         * рекордсета плиток в массив, каждая из которых
                         * может быть группой (контейнером) для набора плиток
                         * @param taskData
                         */
                        function performTileTasksData(taskData) {
                           var tilesModified = [];
                           if (taskData) {
                              taskData.each(function (tileItem, i) {
                                 var tileModified = {};
                                 if (tileItem.get("@СвязьДокументов")) {
                                    tileModified.id = parseInt(tileItem.get("@СвязьДокументов"), 10) || 0;
                                    tileModified.record = tileItem;
                                    tileModified.orderNum = tileItem.get("ПорНомер");
                                    tileModified.docType = tileItem.get('ДокументСледствие.ТипД.ТипД') || tileItem.get('ДокументСледствие.ТипД.Назв') || '';
                                    var hasSearchFilter = tileItem.get("ЕстьФильтрПоМаске") || false,
                                       searchText = hasSearchFilter && (tileItem.get("ФильтрПоМаске") || false );
                                    tileModified['НазваниеПункта'] = highlightText(tileItem.get("ДокументСледствие.ДРасш.Назв"), searchText) ||
                                       highlightText(tileItem.get("ДокументСледствие.РазлД.Инф"), searchText) ||
                                       highlightText(tileItem.get("Номер"), searchText) ||
                                       'Без названия';
                                    //Извлекаем критерий, отображаемый под текстом задачи
                                    tileModified.criteria = highlightText(tileItem.get('Примечание'), searchText) || null;
                                    //Извлекаем плановый статус (не статус столбца)
                                    tileModified.planPointStatus = tileItem.get('ПунктПлана.Состояние') || -1;
                                    tileModified['НазваниеПункта'] = strHelpers.escapeHtml(strHelpers.escapeTagsFromStr(tileModified['НазваниеПункта'], ''));
                                    tileModified.criteria = strHelpers.escapeHtml(strHelpers.escapeTagsFromStr(tileModified.criteria, ''));
                                    tileModified.comment = tileItem.get('Раздел@') ? '' : strHelpers.wrapURLs(strHelpers.escapeHtml(tileItem.get('СвязьДокументовПлан.Комментарий') || '') || '');
                                    tileModified['ИдОтв'] = parseInt(tileItem.get("ДокументСледствие.Сотрудник"), 10) ||
                                       parseInt(tileItem.get("Исполнитель.@Лицо"), 10) || 0;
                                    tileModified.wrkIds = tileItem.get("РП.ИдИсполнителей") || [];
                                    tileModified['ФИООтв'] = tileItem.get("Исполнитель.Название") ||
                                       tileItem.get("ДокументСледствие.Сотр.Назв") || '';

                                    tileModified.branchName = tileItem.get('СтруктураПредприятия.Название') || '';
                                    var mapColor = tileItem.get("GeoMapColor");
                                    tileModified.mapColorCSS = 'border-left-color: #CCC;';
                                    //Формируем CSS код левой полоски на плитке на
                                    //основании цвета исполнителя из БД
                                    if (mapColor && tileModified['ИдОтв']) {
                                       mapColorInt = parseInt(mapColor, 10);
                                       tileModified.mapColorCSS = mapColorInt && mapColorInt >= 0 ? 'border-left-color:#' + mapColorInt.toString(16) + ';' : 'border-left-color: #CCC;';
                                    }
                                    tileModified['ИдФото'] = 0;
                                    tileModified.wrkPhotoIds = tileItem.get("РП.ФотоИсполнителей") || [];
                                    //Извлекаем ИдФото главного исполнителя
                                    if (tileModified.wrkPhotoIds && tileModified.wrkPhotoIds.length && tileModified.wrkPhotoIds.length > 0) {
                                       tileModified['ИдФото'] = tileModified.wrkPhotoIds[0];
                                    }
                                    var wrkNames = tileItem.get("РП.ФИОИсполнителей") || [];
                                    //Извлекаем ФИО главного исполнителя
                                    if (wrkNames && wrkNames.length && wrkNames.length > 0 && !tileModified['ФИООтв']) {
                                       tileModified['ФИООтв'] = wrkNames[0];
                                    }
                                    tileModified.wrkNames = wrkNames;
                                    tileModified['ФИООтв'] = strHelpers.escapeHtml(tileModified['ФИООтв']);
                                    tileModified.branchName = strHelpers.escapeHtml(tileModified.branchName);
                                    tileModified.status = tileItem.get("СвязьДокументовПлан.Состояние") || 0;
                                    tileModified.statusShort = tileItem.get("ДокСледствие.СостКраткое") || 0;
                                    tileModified.reglName = tileItem.get("НазваниеРегламента") || 'Пункт плана';
                                    tileModified.phaseName = tileItem.get("НазваниеФазы") || '';
                                    //Если фаза не найдена, то пробуем определить ее
                                    // через краткое числовое состояние
                                    if (!tileModified.phaseName) {
                                       switch (tileModified.statusShort) {
                                          case 7:
                                             tileModified.phaseName = 'Завершен';
                                             break;
                                          case 9:
                                             tileModified.phaseName = 'Не завершен';
                                             break;
                                          default:
                                             break;
                                       }
                                    }
                                    tileModified.docId = tileItem.get("ДокументСледствие") || tileItem.get("СвязьДокументовПлан.Документ") || 0;
                                    tileModified.docLinkId = tileItem.get("СвязьДокументовПлан.Документ") || 0;
                                    tileModified.resultDocId = tileItem.get("ДокументСледствие") || null;
                                    tileModified['@Документ'] = tileItem.get("ДокументСледствие") || null;
                                    tileModified.baseDocId = tileItem.get("ДокументОснование") || 0;
                                    var complDate = tileItem.get('СвязьДокументовПлан.ПланДата');
                                    tileModified.itsGroupTile = tileItem.get("itsGroupTile") ? tileItem.get("itsGroupTile") : false;
                                    //Получаем текст имени исполнителей на плитке в случае
                                    //если их более одного
                                    if (tileModified.wrkIds && tileModified.wrkIds.length && tileModified.wrkIds.length > 1) {
                                       if (!tileModified.itsGroupTile) {
                                          tileModified['ФИООтв'] += ('+' + (tileModified.wrkIds.length - 1));
                                       }
                                       if (!tileModified['ИдОтв']) {
                                          tileModified['ИдОтв'] = tileModified.wrkIds[0];
                                       }
                                    }
                                    var tileTasks = tileItem.get("tasks");
                                    tileModified.tasks = tileTasks && tileTasks.getCount && tileTasks.getCount() > 0 && tileModified.itsGroupTile ? performTileTasksData(tileTasks) : [];
                                    tileModified.taskCnt = tileModified.tasks && tileModified.tasks.length && tileModified.tasks.length > 0 ? tileModified.tasks.length : '';
                                    tileModified['КоличествоПодзадач'] = tileItem.get("ПроектЗадания") || '0';
                                    tileModified['КоличествоСообщений'] = '0';
                                    tileModified['Веха.Название'] = '';
                                    var projTimeRes = tileItem.get("ПроектРесурсы") || 0,
                                       projTimeHourses = Math.floor(projTimeRes / 60) + ':',
                                       projTimeMinutes = (projTimeRes % 60) + '';
                                    tileModified['ВремяМинут'] = ( projTimeHourses.length < 3 ? '0' + projTimeHourses : projTimeHourses) + ( projTimeMinutes.length < 2 ? '0' + projTimeMinutes : projTimeMinutes);
                                    tileModified['Срок'] = complDate ? complDate.strftime('%d.%m') || '00.00' : '00.00';
                                    tileModified.timeOverflow = false;
                                    complDate = complDate ? complDate.setHours(23, 59, 59) : null;
                                    //Формируем признак просроченности пункта плана
                                    if (complDate && complDate < (new Date())) {
                                       tileModified.timeOverflow = true;
                                    }
                                    //текстовый вид даты начала плана
                                    var planStartDate = tileItem.get('План.ДатаНачала'),
                                       planEndDate = tileItem.get('План.ДатаОкончания'),
                                       currentDate = new Date();
                                    planStartDate = currentDate >= planStartDate && currentDate <= planEndDate ? currentDate : planStartDate;
                                    tileModified.planStartDate = planStartDate ? planStartDate.strftime('%d.%m.%y') : currentDate.strftime('%d.%m.%y');
                                    tileModified['ИдентификаторДокумента'] = tileItem.get("ДокументСледствие.ИдДок") || null;
                                    tileModified['Пометки'] = tileItem.get("ДокументСледствие.Пометки") || null;
                                    tileModified.priority = tileItem.get("СвязьДокументовПлан.Приоритет") || 0;
                                    tileModified['ФИОЗак'] = tileItem.get("Заказчик.Название") || 'Без заказчика';
                                    var reglName = tileModified.reglName || '',
                                       docTypeName = tileModified.docType || '';
                                    //Определяем чиссловое обозначение типа пункта плана (плитки)
                                    //а также его фона подсветки
                                    tileModified.tilePointType = tileModified.tileBackColor = 0;
                                    if (reglName && !tileModified.itsGroupTile) {
                                       if ((docTypeName && (docTypeName.indexOf('Этап') >= 0 || docTypeName.indexOf('Проект') >= 0)) ||
                                          reglName.indexOf('Этап') >= 0 || reglName.indexOf('Проект') >= 0) {
                                          tileModified.tilePointType = tileModified.tileBackColor = 1;
                                          tileModified.phaseName = '';
                                       }
                                       if ((docTypeName && docTypeName.indexOf('Пункт плана') >= 0) || reglName.indexOf('Пункт плана') >= 0) {
                                          tileModified.tilePointType = tileModified.tileBackColor = 2;
                                          tileModified.phaseName = '';
                                       }
                                       docTypeName = docTypeName.toLowerCase();
                                       reglName = reglName.toLowerCase();
                                       var concatName = docTypeName + reglName;
                                       if (concatName.indexOf('проверк') >= 0) {
                                          tileModified.tilePointType = tileModified.tileBackColor = 3;
                                       }
                                       if (concatName.indexOf('пункт проверки') >= 0 || reglName.indexOf('этап проверки') >= 0 || reglName.indexOf('план') >= 0) {
                                          tileModified.phaseName = '';
                                       }

                                    }

                                    tilesModified.push(tileModified);
                                 }

                              });
                           }
                           return tilesModified;
                        }

                        return performTileTasksData(val);
                     }

                  }


               }
            }
         }

      });
   }
);