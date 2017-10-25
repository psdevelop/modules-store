"""
  БЛ для отображения и манипуляций с планом работ
  в плиточном представлении
  автор Полтароков С.П.
"""
import TasksTransfer
import regls
import sbis

from pmanagment.common.common import get_doc_types
from pmanagment.workplan.workplan import get_plan_works


def get_plan_in_tile_format(_filter):
    """
    Основной метод поставки данных для плиточного компонента отображения Плана работ
    :param _filter: (Record) содержит фильтры и параметры запроса данных
    :return: (Boolean) True/False
    """

    manager = regls.GetManager()

    def sort_docs(rec1, rec2):
        """
        Сортировка документов
        :param rec1: (Record) Данные первого рекорда 
        :param rec2: (Record) Данные второго рекорда
        :return: (Boolean) True/False
        """
        order = ("ПроектВПапке", "ПорНомер", "@СвязьДокументов")
        for _ in order:
            value1 = str(rec1[_])
            value2 = str(rec2[_])
            if value1 != value2:
                return value1 > value2 if _ == 'ПроектВПапке' else value1 < value2

        return True

    def get_tasks_of_person_group(group_rec, tasks):
        """
        вспомогательная функция формирования группы плиток
        :param group_rec: (Record) Данные по группе
        :param tasks: (Record) данные по задачам
        :return: (RecordSet)
        """
        _tasks_rs = sbis.CreateRecordSet(tasks.Format())

        for _ in tasks:
            curr_group = str(_.Get("СвязьДокументовПлан.Исполнитель", "")) + "," + str(
                _.Get("СвязьДокументовПлан.Состояние", ""))
            if curr_group == group_rec.Get("persGroupId", ""):
                _tasks_rs.AddRow(_)

        return _tasks_rs

    def group_tasks_by_person(task_list):
        """
        набор группировок по сотрудникам и состоянию
        :param task_list: (RecordSet) Список Задач
        :return: (RecordSet) Сгрупированные данные
        """

        task_list.AddColRecordSet("tasks")
        task_list.AddColBool("itsGroupTile")
        task_list.AddColString("persGroupId")

        person_grouped_rs = sbis.CreateRecordSet(task_list.Format())
        ids_pers_groups = []
        no_pers_ids = []

        for _ in task_list:
            task_id = str(_.Get("СвязьДокументовПлан.Исполнитель", None)) + "," + str(
                _.Get("СвязьДокументовПлан.Состояние", None)) if _.Get("СвязьДокументовПлан.Исполнитель",
                                                                       None) else "None," + str(
                _.Get("СвязьДокументовПлан.Состояние", None))

            if task_id and _.Get("СвязьДокументовПлан.Исполнитель", None):
                if not task_id in ids_pers_groups:
                    _["persGroupId"] = task_id
                    _["tasks"] = get_tasks_of_person_group(_, task_list)
                    _["itsGroupTile"] = True
                    person_grouped_rs.AddRow(_)
                ids_pers_groups.append(task_id)
            else:
                if not task_id in no_pers_ids:
                    no_pers_group = sbis.Record(person_grouped_rs.Format())
                    no_pers_group["@СвязьДокументов"] = -1
                    no_pers_group["СвязьДокументовПлан.Исполнитель"] = None
                    no_pers_group["РазделПроекта@"] = False
                    no_pers_group["Раздел@"] = False
                    no_pers_group["СвязьДокументовПлан.Состояние"] = _.Get("СвязьДокументовПлан.Состояние", None)
                    no_pers_group["Примечание"] = "Без ответственного содержимое"
                    no_pers_group["itsGroupTile"] = True
                    no_pers_group["persGroupId"] = task_id
                    no_pers_group["tasks"] = get_tasks_of_person_group(no_pers_group, task_list)

                    person_grouped_rs.AddRow(no_pers_group)
                no_pers_ids.append(task_id)
        return person_grouped_rs
		
    def sort_tiles_recordset(tasks_rs, is_person_grouping):
        """
        Сортировка рекордсета плиток с группировкой по сотрудникам при необходимости
        :param tasks_rs: (Recordset) Рекорсет с плитками 
		:param is_person_grouping: (Вoolean) Признак группировки по сотрудникам
        :return: (Recordset) True/False
        """
        task_tiles = group_tasks_by_person(tasks_rs) if is_person_grouping else tasks_rs
        task_tiles.SortRows(sort_docs)
        return task_tiles

        # константы типов документов

    DOC_TYPES = get_doc_types()
    task_type = DOC_TYPES.СлужЗап
    proj_type = DOC_TYPES.Проект
    stage_type = DOC_TYPES.Этап
    error_type = DOC_TYPES.РекламацияВнутр

    # признаки группировки по проектам, вывода плоским списком
    # группировки по сотрудникам
    is_proj_grouping = _filter.TestField("ГруппировкаПоПроектам") is not None and _filter.Get("ГруппировкаПоПроектам",
                                                                                              None)
    is_one_section_list = _filter.TestField("ПлоскийСписок") is not None and _filter.Get("ПлоскийСписок", None)
    is_proj_grouping = is_proj_grouping and not is_one_section_list
    is_person_grouping = _filter.TestField("ГруппировкаПоСотрудникам") is not None and _filter.Get(
        "ГруппировкаПоСотрудникам", None)
		
    # извлекаем массив идшников @СвязьДокументов которые не надо выводить
    no_select_link_ids = []
    no_select_filter = ""
    if _filter is not None and _filter.TestField('НеПоказывать') is not None and _filter.Get("НеПоказывать", None):
        no_select_link_ids = _filter.Get("НеПоказывать", None)
    if len(no_select_link_ids) > 0:
        no_select_filter = """ AND NOT (doc_link."@СвязьДокументов" = ANY($7::integer[])) """

    # признаки фильтрации по принадлежности
    filter_by_task_type = False
    filter_by_task_type2 = False
    task_type_filter = _filter.Get("Принадлежность", 0)
    if task_type_filter:
        if task_type_filter == 1:
            filter_by_task_type = True
        if task_type_filter == 2:
            filter_by_task_type2 = True
			
    # признаки фильтрации по отдельному пункту
    point_id_filter = _filter.Get("ИдПункта", 0)

    # условие фильтрации по регламенту
    filter_by_worker_value = _filter.Get("ФильтрИсполнитель", None)
    filter_by_worker = filter_by_worker_value is not None
    filter_by_regl_where = ""
    filter_regl_value = _filter.Get("ФильтрРегламент", None)
    if filter_regl_value is not None:
        filter_by_regl_where = """ AND  (res_doc."ИдРегламента"=$5::uuid OR
            NOT doc_link."Раздел@" IS NULL) """

    # условие фильтрации по маске
    filter_by_mask = _filter.Get("ФильтрПоМаске", None) is not None
    filter_by_mask_where = ""
    mask_filt_join1 = ""
    mask_filt_field1 = ""
    if filter_by_mask:
        mask_filt_join1 = """ LEFT JOIN "РазличныеДокументы" oth_exp_doc
               ON res_doc."@Документ"=oth_exp_doc."@Документ" """
        mask_filt_field1 = """ , true::boolean as "ЕстьФильтрПоМаске",
            $4::TEXT as "ФильтрПоМаске" """
        filter_by_mask_where = """ (lower("ДокументСледствие.ДРасш.Назв") LIKE lower($3::TEXT) OR
                              lower("ДокументСледствие.РазлД.Инф") LIKE lower($3::TEXT) OR
                              lower("ДокументСледствие.Сотр.Назв") LIKE lower($3::TEXT) OR
                              lower("Примечание") LIKE lower($3::TEXT) OR
                              lower("Заказчик.Название") LIKE lower($3::TEXT) OR
                              lower("Номер") LIKE lower($3::TEXT) OR NOT "Раздел@" IS NULL )
       """

    user = sbis.Пользователь.СвязьТекущегоПользователя()
    user_id = 0
    if user:
        user_id = user.Get("ЧастноеЛицо", 0)

    # условие фильтрации по заказчику
    final_where = ""
    if filter_by_task_type2 and user_id:
        final_where = """ WHERE ({1}."СвязьДокументовПлан.Заказчик"={0}::INTEGER OR NOT {1}."Раздел@" IS NULL) """.format(user_id, "T2" if is_proj_grouping else "T0")

    # подзапрос подтягивания фаз
    # при группировке по проектам
    t2_sql = """ SELECT DISTINCT ON (T2."@СвязьДокументов") T2.*, event."ИдФазы",
                  ARRAY(SELECT empls."Лицо" FROM empls WHERE empls."@СвязьДокументов" = T2."@СвязьДокументов") AS "РП.ИдИсполнителей",
                  ARRAY(SELECT empls."ФИО" FROM empls WHERE empls."@СвязьДокументов" = T2."@СвязьДокументов") AS "РП.ФИОИсполнителей",
                  ARRAY(SELECT empls."Фото" FROM empls WHERE empls."@СвязьДокументов" = T2."@СвязьДокументов") AS "РП.ФотоИсполнителей"
                FROM T2 LEFT JOIN "Событие" event
                  ON event."Документ" = T2."ДокументСледствие"
                     AND T2."ДокСледствие.ТипДок" IN ({3}::INTEGER,{4}::INTEGER) --СлужЗап, РекламацияВнутр
                     AND event."Конец" IS NULL
                     AND event."Тип" = 0
                     AND event."идСлужебнойФазы" IS NULL
                     AND event."Документ" IS NOT NULL {5}  """.format(0, proj_type,
                                                                      stage_type, task_type, error_type, final_where)

    # обертка запроса для фильтрации по исполнителям и ФильтрПоМаске
    # при группировке по проектам
    if filter_by_task_type or filter_by_worker or filter_by_mask:
        if (task_type_filter == 1 and user_id) or filter_by_worker:
            t2_sql = """, T4 AS ({0})
               SELECT T4.* FROM T4 WHERE $1::INTEGER = ANY (T4."РП.ИдИсполнителей") {1}
                   """.format(t2_sql, (" AND " + filter_by_mask_where) if filter_by_mask else "")
        else:
            t2_sql = """, T4 AS ({0})
               SELECT T4.* FROM T4 WHERE {1}
                   """.format(t2_sql, filter_by_mask_where)

    # завершающий элемент запроса перед вставкой в основной запрос
    # при группировке по проектам - содержит рекурсив
    # поиска проекта в который входит задача
    final_sql = """,
         T3 as (
            SELECT t3d."@Документ", t3d."Раздел", t3d."Раздел@", t3d."ТипДокумента",
               1 as "Уровень", t3d."Сотрудник", t3dl."ДокументСледствие" as tmpdoc
            FROM "СвязьДокументов" t3dl INNER JOIN "Документ" t3d
            ON t3dl."ДокументОснование" = t3d."@Документ"
            WHERE
               t3dl."ДокументСледствие" IN (SELECT "ДокументСледствие" FROM T0
                  WHERE "ДокСледствие.ТипДок" IN ({3}::INTEGER, {4}::INTEGER))
               AND t3dl."ВидСвязи"=1

            UNION

            SELECT "@Документ", "Раздел", "Раздел@", "ТипДокумента", 1 as "Уровень",
               "Сотрудник", "@Документ" as tmpdoc
            FROM "Документ"
            WHERE "@Документ" IN (SELECT "ДокументСледствие" FROM T0
                  WHERE "ДокСледствие.ТипДок" IN ({1}::INTEGER, {2}::INTEGER))
         ),

         tmp as (
            SELECT "@Документ", "Раздел", "Раздел@", "ТипДокумента", "Уровень", "Сотрудник",
               tmpdoc
            FROM T3
            WHERE "ТипДокумента" IN ({1}::INTEGER,{2}::INTEGER)

            UNION

            SELECT doc."@Документ", doc."Раздел", doc."Раздел@", doc."ТипДокумента",
               tmp."Уровень" + 1, doc."Сотрудник", tmp.tmpdoc
            FROM tmp
            INNER JOIN "Документ" doc
               ON tmp."Раздел" = doc."@Документ"
   	      WHERE doc."ТипДокумента" IN ({1}::INTEGER,{2}::INTEGER)  AND tmp."ТипДокумента" <> {1}::INTEGER
   	   ),

         T1 AS (
            SELECT tmp."@Документ" as "РП.РодительскийПроект",
   	             NULL :: INTEGER[] as "ПрРазделы",
   	             tmp."Раздел" as tmpsect,
   	             doc_ex."Название" as "Проект.Название",
   	             NULL :: TEXT AS "ПрИнформация",
   	             tmp."ТипДокумента" as tmpdtype,
   	             NULL :: TEXT AS "ПрТип",
   	             tmp.tmpdoc, tmp."Уровень",
   	             tmp."Сотрудник" as "Проект.ИдСотрудника",
   	             face."Название" AS "Проект.Лицо",
   	             pr."Описание" as "Проект.Описание",
                   pr."ПланДатаНач" as "Проект.ПланДатаНач",
                   pr."ПланДатаКнц" as "Проект.ПланДатаКнц",
				        pr_pers."Фото" as "Проект.Сотрудник.ИдФото",
						  org_srt."Название" as "Проект.Сотр.Подразделение"
   	      FROM  tmp
   	      INNER JOIN "ДокументРасширение" doc_ex
   	         ON tmp."@Документ" = doc_ex."@Документ"
   	      INNER JOIN "Лицо" face
   	         ON tmp."Сотрудник" = face."@Лицо"
            LEFT JOIN "Проект" pr
               ON tmp."@Документ"=pr."@Документ"
			    LEFT JOIN "ЧастноеЛицо" pr_pers
					  ON face."@Лицо"=pr_pers."@Лицо"
				 LEFT JOIN "СтруктураПредприятия" org_srt
					  ON face."@Лицо" = org_srt."@Лицо"
            WHERE tmp."ТипДокумента"={1}::INTEGER
            ORDER BY tmp."@Документ" ASC NULLS LAST
   	   ),

   	   T2 AS (
   	      SELECT DISTINCT ON (T0."@СвязьДокументов")
   	         T0.*, T1."РП.РодительскийПроект", T1."Проект.Название",
   	         T1."Проект.ИдСотрудника", T1."Проект.Лицо",
   	         T1."Проект.Описание", T1."Проект.ПланДатаНач",
               T1."Проект.ПланДатаКнц", T1."Проект.Сотрудник.ИдФото", 
			      T1."Проект.Сотр.Подразделение"
            FROM T0 LEFT JOIN T1
   	         ON T1.tmpdoc = T0."ДокументСледствие"
   	            )

               {5} """.format(0, proj_type, stage_type, task_type, error_type, t2_sql)

    # подзапрос подтягивания фаз
    # при группировке по папкам или выводе плоским списком
    t0_sql = """ SELECT 
                   DISTINCT ON (T0."@СвязьДокументов") T0.*, 
                   event."ИдФазы", 
                   NULL::INTEGER AS "РП.РодительскийПроект",
                   NULL::TEXT AS "Проект.Название", NULL::INTEGER AS "Проект.ИдСотрудника",
                   NULL::INTEGER AS "Проект.Лицо",
                   ARRAY(SELECT empls."Лицо" FROM empls WHERE empls."@СвязьДокументов" = T0."@СвязьДокументов") AS "РП.ИдИсполнителей",
                   ARRAY(SELECT empls."ФИО" FROM empls WHERE empls."@СвязьДокументов" = T0."@СвязьДокументов") AS "РП.ФИОИсполнителей",
                   ARRAY(SELECT empls."Фото" FROM empls WHERE empls."@СвязьДокументов" = T0."@СвязьДокументов") AS "РП.ФотоИсполнителей"
                   FROM T0 LEFT JOIN "Событие" event
                            ON event."Документ" = T0."ДокументСледствие"
                            AND event."Конец" IS NULL
                            AND event."Тип" = 0
                            AND event."идСлужебнойФазы" IS NULL
                            AND event."Документ" IS NOT NULL {5} """.format(0, proj_type,
                                                                            stage_type, task_type, error_type,
                                                                            final_where)

    # обертка запроса для фильтрации по исполнителям и ФильтрПоМаске
    # при группировке по папкам или выводе плоским списком
    if is_one_section_list or not is_proj_grouping:
        # завершающий элемент запроса перед вставкой в основной запрос
        # при группировке по папкам или выводе плоским списком
        final_sql = t0_sql
        if filter_by_task_type or filter_by_worker or filter_by_mask:
            if (task_type_filter == 1 and user_id) or filter_by_worker:
                final_sql = """, T4 AS ({0})
                   SELECT T4.* FROM T4 WHERE ($1::INTEGER = ANY (T4."РП.ИдИсполнителей") {1}) OR NOT "Раздел@" IS NULL
                            """.format(t0_sql, (" AND " + filter_by_mask_where) if filter_by_mask else "")
            else:
                final_sql = """, T4 AS ({0})
                   SELECT T4.* FROM T4 WHERE {1} OR NOT "Раздел@" IS NULL
                            """.format(t0_sql, filter_by_mask_where)

    # условие обрезки папок при группировке по проектам
    # и выводе плоским списком
    folders_del_where = ""
    if is_one_section_list or is_proj_grouping:
        folders_del_where = """ AND doc_link."Раздел@" IS NULL """

    # основной заголовочный запрос - главное декартовое произведение
    # общая часть всех типов запросов к плану работ для его плиточного отображения
    sql = """ WITH RECURSIVE T0 AS (
         SELECT DISTINCT ON (doc_link."@СвязьДокументов")
            doc_link."@СвязьДокументов", 
            doc_link."Примечание",
            doc_link."ПорНомер", 
            doc_link."Номер",
            doc_link."ДокументОснование", 
            doc_link."ДокументСледствие",
            doc_link."Раздел",
            doc_link."Раздел@",
            
            emp."Название" as "ДокументСледствие.Сотр.Назв",
            
            res_exp_doc."Название" as "ДокументСледствие.ДРасш.Назв",
            res_exp_doc."СостояниеКраткое" as "ДокСледствие.СостКраткое",
            res_exp_doc."Срок" as "ДокументСледствие.Срок",
            
            dtype."ТипДокумента" as "ДокументСледствие.ТипД.ТипД",
            dtype."НазваниеКраткое" as "ДокументСледствие.ТипД.НазвКр",
            dtype."ИмяДиалога" as "ДокументСледствие.ТипД.ИмяДиалога",
            dtype."Название" as "ДокументСледствие.ТипД.Назв",
            
            plan_dlink."ПланДата" as "СвязьДокументовПлан.ПланДата",
            plan_dlink."Исполнитель" as "СвязьДокументовПлан.Исполнитель",
            plan_dlink."Заказчик" as "СвязьДокументовПлан.Заказчик",
            plan_dlink."Выполнен" as "СвязьДокументовПлан.Выполнен",
            plan_dlink."Проверен" as "СвязьДокументовПлан.Проверен",
            plan_dlink."Заполнен" as "СвязьДокументовПлан.Заполнен",
            plan_dlink."Комментарий" as "СвязьДокументовПлан.Комментарий",
            plan_dlink."Цвет" as "СвязьДокументовПлан.Цвет",
            plan_dlink."Приоритет" as "СвязьДокументовПлан.Приоритет",
            plan_dlink."Состояние" as "СвязьДокументовПлан.Состояние",
            plan_dlink."Состояние" as "ПунктПлана.Состояние",
            plan_dlink."Документ" as "СвязьДокументовПлан.Документ",
            
            cust_pers."Название" as "Заказчик.Название",
            cust_pers."@Лицо" as "Заказчик.@Лицо", 
            
            exec_pers."Название" as "Исполнитель.Название",
            exec_pers."@Лицо" as "Исполнитель.@Лицо",
            
            oth_exp_doc."Информация" as "ДокументСледствие.РазлД.Инф",
            
            base_doc."Лицо3" as "ДокументОснование.Лицо3",
            base_doc."ЛицоСоздал" as "ДокументОснование.ЛицоСоздал",
            base_doc."Сотрудник" as "ДокументОснование.Сотрудник",
            base_doc."Удален" as "ДокументОснование.Удален",
            
            base_exp_doc."Состояние" as "ДокументРасширение.Состояние",
            
            res_doc."ИдРегламента", 
            res_doc."ТипДокумента" AS "ДокСледствие.ТипДок",
            res_doc."Сотрудник" as "ДокументСледствие.Сотрудник",
            res_doc."ИдентификаторДокумента" as "ДокументСледствие.ИдДок",
            res_doc."Пометки" as "ДокументСледствие.Пометки",
            res_doc."Дата" as "ДокументСледствие.Дата",
            
            NULL::TEXT AS "НазваниеРегламента",
            NULL::TEXT AS "НазваниеФазы", NULL::INTEGER AS "ПорНомерФазы",
            NULL::boolean as "ПлоскийСписок", 
            NULL::INTEGER AS "РазделПроекта", NULL::boolean as "РП.РазрешеноРедактировать",
            NULL::INTEGER AS "GeoMapColor", 
            NULL::boolean as "РазделПроекта@",
            NULL::boolean AS "ПроектВПапке",
            NULL::date AS "План.ДатаНачала",
            NULL::date AS "План.ДатаОкончания",
            
            resd_pr."Задания" as "ПроектЗадания",
            resd_pr."Ресурсы" as "ПроектРесурсы", 
            resd_pr."ДатаПодсчета" as "ПроектДатаПодсчета",
            
            pr_pers."Фото" as "Исполнитель.Фото", usr_links."MapColor",
            
            org_srt."Название" as "СтруктураПредприятия.Название" {9}
            
         FROM "СвязьДокументов" AS doc_link
         
         INNER JOIN "Документ" AS base_doc
            ON doc_link."ДокументОснование" = base_doc."@Документ"
            
         INNER JOIN "СвязьДокументовПлан" AS plan_dlink
            ON doc_link."@СвязьДокументов" = plan_dlink."@СвязьДокументов"

         LEFT JOIN "Документ" AS res_doc
            ON doc_link."ДокументСледствие" = res_doc."@Документ"
            
         LEFT JOIN "ДокументРасширение" AS base_exp_doc
            ON base_doc."@Документ" = base_exp_doc."@Документ"

         LEFT JOIN "Лицо" AS emp
            ON res_doc."Сотрудник" = emp."@Лицо"
            
         LEFT JOIN "ТипДокумента" AS dtype
            ON res_doc."ТипДокумента" = dtype."@ТипДокумента"
            
         LEFT JOIN "Лицо" AS sec_pers
            ON res_doc."Лицо2" = sec_pers."@Лицо"
            
         LEFT JOIN "ДокументРасширение" AS res_exp_doc
            ON res_doc."@Документ" = res_exp_doc."@Документ"
            
         LEFT JOIN "РазличныеДокументы" AS oth_exp_doc
            ON res_doc."@Документ" = oth_exp_doc."@Документ"
            
         LEFT JOIN "Проект" AS resd_pr
            ON res_doc."@Документ" = resd_pr."@Документ"

         LEFT JOIN "Лицо" AS exec_pers
            ON plan_dlink."Исполнитель" = exec_pers."@Лицо"
            
         LEFT JOIN "Лицо" AS cust_pers
            ON plan_dlink."Заказчик" = cust_pers."@Лицо"

         LEFT JOIN "ЧастноеЛицо" AS pr_pers
            ON exec_pers."@Лицо" = pr_pers."@Лицо"

         LEFT JOIN "СвязиПользователя" AS usr_links
            ON pr_pers."@Лицо" = usr_links."ЧастноеЛицо"
            
         LEFT JOIN "СтруктураПредприятия" AS org_srt
            ON usr_links."СтруктураПредприятия" = org_srt."@Лицо"

         WHERE 
             doc_link."ДокументОснование"=$2::INTEGER AND 
             doc_link."ВидСвязи" IS NULL AND
             plan_dlink."@СвязьДокументов" IS NOT NULL AND 
            ( plan_dlink."@СвязьДокументов"=$6::INTEGER OR 0=$6::INTEGER )
            {6} {7} {10}
      ),

      empls AS (
         SELECT 
            T0."@СвязьДокументов", 
            "ЛицоДокумента"."Лицо" AS "Лицо",
            "ЧастноеЛицо"."Фото", 
            "ЛицоДокумента"."Время" AS "Время",
            (T0."СвязьДокументовПлан.Исполнитель" = "ЛицоДокумента"."Лицо") AS "ОсновнойИсполнитель",
            coalesce("ЧастноеЛицо"."Фамилия", '') ||' '||left(coalesce("ЧастноеЛицо"."Имя", ''),1)||'.'||left(coalesce("ЧастноеЛицо"."Отчество", ''),1)||'.' AS "ФИО"
         
         FROM T0 
         INNER JOIN "ЛицоДокумента"
            ON "ЛицоДокумента"."СвязьДокументов" = T0."@СвязьДокументов"
         LEFT JOIN "ЧастноеЛицо"
            ON "ЧастноеЛицо"."@Лицо" = "ЛицоДокумента"."Лицо"
         ORDER BY "ОсновнойИсполнитель" DESC
      )
      {5}
               """.format(0,
                          proj_type,
                          stage_type,
                          task_type,
                          error_type,
                          final_sql,
                          filter_by_regl_where,
                          folders_del_where,
                          mask_filt_join1,
                          mask_filt_field1,
                          no_select_filter)

    maks_filter_text = _filter.Get("ФильтрПоМаске", None) if _filter.Get("ФильтрПоМаске", None) else ''

    result = sbis.SqlQuery(
        sql,
        user_id if filter_by_task_type else filter_by_worker_value if filter_by_worker_value else 0,
        _filter.Get("ИдПланаРабот", 0), '%' + maks_filter_text + '%', maks_filter_text,
        filter_regl_value if filter_regl_value else "", point_id_filter if point_id_filter else 0,
        no_select_link_ids
    )

    # массив идшников всех исполнителей
    worker_ids = []
    # извлекаем массив идшников фаз проверки
    check_phases = []
    if _filter is not None and _filter.TestField('ФазыПроверки') is not None and _filter.Get("ФазыПроверки", None):
        check_phases = _filter.Get("ФазыПроверки", None)
    # извлекаем массив идшников фаз сборки
    build_phases = []
    if _filter is not None and _filter.TestField('ФазыСборки') is not None:
        build_phases = _filter.Get("ФазыСборки", None)
    # извлекаем массив идшников фаз выполнения
    processing_phases = []
    if _filter is not None and _filter.TestField('ФазыВыполнения') is not None:
        processing_phases = _filter.Get("ФазыВыполнения", None)
    # извлекаем массив идшников фаз Запланировано
    planned_phases = []
    if _filter is not None and _filter.TestField('ФазыЗапланировано') is not None:
        planned_phases = _filter.Get("ФазыЗапланировано", None)
    # извлекаем массив идшников фаз Выполнено
    complete_phases = []
    if _filter is not None and _filter.TestField('ФазыВыполнено') is not None:
        complete_phases = _filter.Get("ФазыВыполнено", None)

    for rec in result:
        rec["План.ДатаНачала"] = _filter.Get("ФильтрПланДатаНач", None)
        rec["План.ДатаОкончания"] = _filter.Get("ФильтрПланДатаКнц", None)
        # извлекаем и складываем в массив идшники первых (главных) исполнителей  
        all_wrk_ids = rec.Get("РП.ИдИсполнителей", None) or None
        if all_wrk_ids:
            worker_ids.append(all_wrk_ids[0])

        # Обрабатываем состояния пунктов плана работ 0,1,2 = 0,1,2,3,4
        # Под количество столбцов плитки
        # Состояние выполнения раскидывается на три стобца 1,2,3

        if rec.Get("СвязьДокументовПлан.Состояние", None) is None:
            rec["СвязьДокументовПлан.Состояние"] = 0

        if rec.Get("СвязьДокументовПлан.Состояние", None) > 1:
            rec["СвязьДокументовПлан.Состояние"] = rec.Get("СвязьДокументовПлан.Состояние", None) + 2

        # если вылезает за пять столбцов
        # помещаем в последний
        if rec.Get("СвязьДокументовПлан.Состояние", None) > 4:
            rec["СвязьДокументовПлан.Состояние"] = 4

        if not rec.Get("ДокументСледствие", None) is None:
            rec_id = int(rec["ДокументСледствие"])

            regl_uuid = rec.Get("ИдРегламента", None)
            phase_uuid = None
            reg = None
            reg_name = ''
            if regl_uuid:
                reg = manager.Get(regl_uuid)
                if reg:
                    phase_uuid = rec.Get("ИдФазы", None)
                    reg_name = reg.Name()
            # распределяем состояние Выполнение
            # по столбцам Выполнение, Сборка и Проверка
            if phase_uuid:
                phase_obj = reg.GetPhase(phase_uuid)
                if rec.Get("СвязьДокументовПлан.Состояние", None) == 1:
                    sbis.LogMsg("phase_uuid: {}".format(phase_uuid))
                    # если фаза в списке фаз Проверки
                    if str(phase_uuid) in check_phases:
                        rec["СвязьДокументовПлан.Состояние"] = 3
                    # если фаза в списке фаз Сборки
                    if str(phase_uuid) in build_phases:
                        rec["СвязьДокументовПлан.Состояние"] = 2
                    # если фаза в списке фаз Выполнение
                    if str(phase_uuid) in processing_phases:
                        rec["СвязьДокументовПлан.Состояние"] = 1
                # если фаза в списке фаз Запланировано
                if str(phase_uuid) in planned_phases:
                    rec["СвязьДокументовПлан.Состояние"] = 0
                # если фаза в списке фаз Выполнено
                if str(phase_uuid) in complete_phases:
                    rec["СвязьДокументовПлан.Состояние"] = 4
            else:
                phase_obj = None
            if phase_obj:
                phase_name = phase_obj.Name()
                phase_order = phase_obj.Order()
            else:
                phase_name = "Не запущенные"
                #Перемещаем в столбец Выполнено если ДО задачи завершен
                if rec.Get("ДокСледствие.СостКраткое", None) == 7:
                    phase_name = "Завершен"
                    rec["СвязьДокументовПлан.Состояние"] = 4
                #В столбец Выполнено если ДО завершен неудовлетворительно
                elif rec.Get("ДокСледствие.СостКраткое", None) == 9:
                    phase_name = "Не завершен"
                    rec["СвязьДокументовПлан.Состояние"] = 4
                phase_order = None
            rec["НазваниеРегламента"] = reg_name
            rec["НазваниеФазы"] = phase_name
            rec["ПорНомерФазы"] = phase_order

    # удаляем одинаковые идшники исполнителей
    workers_set = set(worker_ids)
    workers_ids = list(workers_set)
    # получаем цвета исполнителей и укладываем их в словарь
    workers_clr = sbis.Geo.getColorFaces(workers_ids, False)

    # блок кода группировки по проектам или папкам
    # в формат данных плиточного компонента
    if result:
        ids_prj = []
        flag = True
        no_prj = None
        no_prj_tiles = None
        idx = 0
        del_row = []
        tdel_row = []
        result.AddColRecordSet("tiles")
        projs_rs = sbis.CreateRecordSet(result.Format())
        parent_field = "РП.РодительскийПроект" if is_proj_grouping else "Раздел"

        for rec in result:
            # извлекаем цвет плитки по цвету главного исполнителя
            rec_wrk_ids = rec.Get("РП.ИдИсполнителей", None) or None
            if workers_clr and rec_wrk_ids:
                face_color = workers_clr.TryFindRowByKey(rec_wrk_ids[0])
                if face_color:
                    rec["GeoMapColor"] = face_color.Get("MapColor", 0)

            sec = rec.Get("Раздел@", None)
            if sec and sec is True:
                del_row.append(idx)
            else:
                rec_id = rec.Get(parent_field, None)

                if rec_id and not is_one_section_list:
                    # Извлекаем все идшники проектов или папок
                    if not (rec_id in ids_prj):
                        projs_rs.AddRow(rec)
                    ids_prj.append(rec_id)
                else:
                    if flag:
                        # создаем папку Без проектов/Баз папок
                        if is_proj_grouping:
                            no_prj = sbis.Record(result.Format())
                            no_prj["@СвязьДокументов"] = 0
                            no_prj["РазделПроекта@"] = True
                            no_prj["Раздел@"] = True
                            no_prj["Примечание"] = "Без проектов"
                            if is_proj_grouping:
                                no_prj["РП.РодительскийПроект"] = -1

                        # создаем подчиненную запись папки Без проектов/папок
                        # в свойства которой будет укладывать пункты плана
                        # без родителя
                        no_prj_tiles = sbis.Record(result.Format())
                        no_prj_tiles["@СвязьДокументов"] = -1
                        no_prj_tiles["РазделПроекта"] = 0 if is_proj_grouping else None
                        no_prj_tiles["Раздел"] = 0 if is_proj_grouping else None
                        no_prj_tiles["РазделПроекта@"] = False
                        no_prj_tiles["Раздел@"] = False
                        no_prj_tiles[
                            "Примечание"] = "Без проектов содержимое" if is_proj_grouping else "Без папок содержимое"
                        flag = False
            idx += 1

        # Если образована запись Без проектов/папок
        if no_prj_tiles:
            tasks_rs = sbis.CreateRecordSet(result.Format())
            tidx = 0
            for tsk_rec in result:
                # Если пункт плана без родителя (проекта/папки)
                if tsk_rec.Get(parent_field, None) is None and tsk_rec.Get("Раздел@", None) is None \
                        or is_one_section_list:
                    tasks_rs.AddRow(tsk_rec)
                    tdel_row.append(tidx)
                tidx += 1
            # укладываем список пунктов без родителя в свойство
            no_prj_tiles["tiles"] = sort_tiles_recordset(tasks_rs, is_person_grouping)
            if tdel_row:
                for i in range(len(tdel_row) - 1, -1, -1):
                    result.DelRow(tdel_row[i])

        # перебираем всех родителей
        for rec in projs_rs:
            if is_proj_grouping:
                id_fold = str(rec.Get(parent_field, None)) + ",Проект"
                rec_prj = sbis.Record(result.Format())
                rec_prj["@СвязьДокументов"] = id_fold
                rec_prj["РазделПроекта@"] = True
                rec_prj["Раздел@"] = True
                rec_prj["СвязьДокументовПлан.Документ"] = rec.Get(parent_field, None)
                rec_prj["Проект.Описание"] = rec.Get("Проект.Описание", None)
                rec_prj["Проект.ПланДатаНач"] = rec.Get("Проект.ПланДатаНач", None)
                rec_prj["Проект.ПланДатаКнц"] = rec.Get("Проект.ПланДатаКнц", None)
                rec_prj["Проект.Лицо"] = rec.Get("Проект.Лицо", None)
                rec_prj["Проект.ИдСотрудника"] = rec.Get("Проект.ИдСотрудника", None)
                rec_prj["Проект.Сотрудник.ИдФото"] = rec.Get("Проект.Сотрудник.ИдФото", None)
                rec_prj["Проект.Сотр.Подразделение"] = rec.Get("Проект.Сотр.Подразделение", None)
                rec_prj["РП.РодительскийПроект"] = rec.Get("РП.РодительскийПроект", None)
                rec_prj["Примечание"] = rec.Get("Проект.Название", None)
                rec_prj["СвязьДокументовПлан.Исполнитель"] = rec.Get("Проект.ИдСотрудника", None)
                rec_prj["Исполнитель.@Лицо"] = rec.Get("Проект.ИдСотрудника", None)
                rec_prj["Исполнитель.Название"] = rec.Get("Проект.Лицо", None)
                result.AddRow(rec_prj)

            id_fold = str(-1 * rec.Get(parent_field, None)) + ",ПроектСодержимое"
            rec_prj = sbis.Record(result.Format())
            rec_prj["@СвязьДокументов"] = id_fold
            rec_prj["РазделПроекта"] = rec.Get(parent_field, None)
            rec_prj["Раздел"] = rec.Get(parent_field, None)
            rec_prj["РазделПроекта@"] = False
            rec_prj["Раздел@"] = False
            rec_prj["СвязьДокументовПлан.Документ"] = -1 * rec.Get(parent_field, None)
            rec_prj["Примечание"] = (
                rec.Get("Проект.Название", None) + " (содержимое)") if is_proj_grouping and rec.Get(
                "Проект.Название", None) else "Папка без имени содержимое"
            rec_prj["СвязьДокументовПлан.Исполнитель"] = rec.Get("Проект.ИдСотрудника", None)
            rec_prj["Исполнитель.@Лицо"] = rec.Get("Проект.ИдСотрудника", None)
            rec_prj["Исполнитель.Название"] = rec.Get("Проект.Лицо", None)

            tasks_rs = sbis.CreateRecordSet(result.Format())
            del_row = []
            idx = 0
            prj_in_folder = False
            for tsk_rec in result:
                if is_proj_grouping:
                    prj_id = rec.Get(parent_field, None)
                    if prj_id and prj_id == rec.Get("ДокументСледствие", None):
                        prj_in_folder = True
                if tsk_rec.Get(parent_field, None) == rec.Get(parent_field, None) \
                        and tsk_rec.Get("Раздел@", None) is None:
                    tasks_rs.AddRow(tsk_rec)
                    del_row.append(idx)
                idx += 1
            for i in range(len(del_row) - 1, -1, -1):
                result.DelRow(del_row[i])
            rec_prj["tiles"] = sort_tiles_recordset(tasks_rs, is_person_grouping)
            rec_prj["ПроектВПапке"] = prj_in_folder
            result.AddRow(rec_prj)

        result.SortRows(sort_docs)

        if no_prj:
            result.AddRow(no_prj)
        if no_prj_tiles:
            result.AddRow(no_prj_tiles)

    return result


def get_plan_tiles_subtasks_count(docs):
    """         
    Метод получения количества подзадач для списка задач                
    :param docs: (List) Список идентификаторов документов задач        
    :return:         
    """
    sql = """ WITH RECURSIVE overtasks AS (
              SELECT diffDocs."@Документ" AS "@Документ", diffDocs."@Документ" AS initdoc
              FROM "РазличныеДокументы" diffDocs
              WHERE ("@Документ"=any($1::integer[])) 
              UNION
              SELECT diffDocs_r."@Документ", overtasks1.initdoc
              FROM "РазличныеДокументы" AS diffDocs_r
              JOIN overtasks AS overtasks1 ON overtasks1."@Документ" = diffDocs_r."БазовыйДокумент"
            ),
           act_tasks as (
              SELECT DISTINCT ON (overtasks."@Документ") 
                     overtasks."@Документ", overtasks.initdoc					
              FROM overtasks 
              INNER JOIN "Документ" doc 
                 ON doc."@Документ" = overtasks."@Документ"
                 AND doc."Удален" IS NOT TRUE
                 AND doc."$Черновик" IS NULL
              INNER JOIN "Событие" event
                 ON event."Документ" = overtasks."@Документ"
                 AND event."Конец" IS NULL
                 AND event."Тип" = 0
                 AND event."идСлужебнойФазы" IS NULL )
           SELECT initdoc as "@Документ", COUNT(*) as "КоличествоПодзадач"
           FROM act_tasks WHERE NOT ("@Документ" = any($1::integer[])) GROUP BY initdoc """

    result = sbis.SqlQuery(sql, docs)

    return result


def get_plan_tiles_milestones_info(docs):
    """         
    Метод возвращающий информацию о прикрепленных вехах для списка задач               
    :param docs: (List) Список идентификаторов документов задач        
    :return:         
    """
    sql = """ WITH T1 AS (
       SELECT T."@Документ", mdoc."@Документ" as mid,
           (coalesce(document_extension."Название"::text,
               ' № ' || mdoc."Номер"::text || ' от ' || milestone."Дата"::text) )  AS "Веха.Название"
       FROM "Документ" T
       INNER JOIN "СвязьДокументов" dl
           ON dl."ДокументСледствие" = T."@Документ" AND dl."ВидСвязи" = 8
       INNER JOIN "Документ" mdoc
           ON dl."ДокументОснование" = mdoc."@Документ"
       INNER JOIN "ДокументРасширение" document_extension
           ON mdoc."@Документ" = document_extension."@Документ"
       INNER JOIN "Веха" milestone
           ON document_extension."@Документ" = milestone."@Документ"
       WHERE T."@Документ"=any($1::integer[])
       ORDER BY T."@Документ", mdoc."@Документ" DESC
       )
       SELECT DISTINCT ON (T2."@Документ") T2."@Документ",
           T1.mid  AS "Веха", T1."Веха.Название"
       FROM "Документ" T2
       INNER JOIN T1
           ON T2."@Документ"=T1."@Документ" """

    result = sbis.SqlQuery(sql, docs)

    return result


def get_plan_point_moving_info(points):
    """         
    Метод, возвращающий информацию переносах между планами для списка задач
    :param points: (List) Список идентификаторов пунктов плана       
    :return:         
    """

    # получение данных: куда перенесён пункт
    dict_name = {}
    sql = """
        WITH T0 AS (
            SELECT 
                lnkp."@СвязьДокументов", 
                CASE WHEN trim(coalesce(p."Описание", '')) = '' THEN struct."Название" ELSE p."Описание" END AS "Описание",
                p."@Документ", 
                p."ПланДатаНач", 
                p."ПланДатаКнц"
            FROM "Проект" p
            JOIN "СвязьДокументов" lkp
                ON p."@Документ" =  lkp."ДокументОснование"
            JOIN "СвязьДокументовПлан" lkp1
                ON lkp."@СвязьДокументов" =  lkp1."@СвязьДокументов"
            JOIN "СвязьДокументов" lk2
                ON lk2."ДокументОснование" = lkp1."Документ" AND lk2."ВидСвязи" = 1
            JOIN "СвязьДокументовПлан" lnkp
                ON lnkp."Документ" = lk2."ДокументСледствие"
            LEFT JOIN "Документ" doc 
                ON doc."@Документ" = lkp."ДокументОснование"
            LEFT JOIN "СтруктураПредприятия" struct 
                ON struct."@Лицо" = doc."Подразделение"
            
            WHERE 
                lnkp."@СвязьДокументов" = ANY($1::integer[])
            ORDER BY lk2."@СвязьДокументов" ASC
        )
        
        SELECT DISTINCT ON (T0."@СвязьДокументов") T0.* FROM T0
   """
    result = sbis.SqlQuery(sql, points)
    result.AddColInt32("ПеренесенВ.Документ")
    result.AddColString("ПеренесенВ.Описание")
    result.AddColDate("ПеренесенВ.ПланДатаНач")
    result.AddColDate("ПеренесенВ.ПланДатаКнц")
    for r_p in result:
        r_p["ПеренесенВ.Документ"] = r_p.Get("@Документ", None)
        r_p["ПеренесенВ.Описание"] = r_p.Get("Описание", None)
        r_p["ПеренесенВ.ПланДатаНач"] = r_p.Get("ПланДатаНач", None)
        r_p["ПеренесенВ.ПланДатаКнц"] = r_p.Get("ПланДатаКнц", None)
        id_lk = r_p.Get("@СвязьДокументов", None)
        dict_name[id_lk] = [r_p.Get("Описание", None), r_p.Get("@Документ", None), r_p.Get("ПланДатаНач", None),
                            r_p.Get("ПланДатаКнц", None)]
        r_p["@Документ"] = None
        r_p["Описание"] = None
        r_p["ПланДатаНач"] = None
        r_p["ПланДатаКнц"] = None

    # получение данных: откуда перенесён пункт
    sql = """
        WITH T0 AS (
            SELECT 
                lnkp."@СвязьДокументов",
                CASE WHEN trim(coalesce(p."Описание", '')) = '' THEN struct."Название" ELSE p."Описание" END AS "Описание",
                p."@Документ", 
                p."ПланДатаНач", 
                p."ПланДатаКнц"
            FROM "Проект" p
            JOIN "СвязьДокументов" lkp
                ON p."@Документ" =  lkp."ДокументОснование"
            JOIN "СвязьДокументовПлан" lkp1
                ON lkp."@СвязьДокументов" =  lkp1."@СвязьДокументов"
            JOIN "СвязьДокументов" lk2
                ON lk2."ДокументСледствие" = lkp1."Документ" AND lk2."ВидСвязи" = 1
            JOIN "СвязьДокументовПлан" lnkp
                ON lnkp."Документ" = lk2."ДокументОснование"
            LEFT JOIN "Документ" doc 
                ON doc."@Документ" = lkp."ДокументОснование"
            LEFT JOIN "СтруктураПредприятия" struct 
                ON struct."@Лицо" = doc."Подразделение"
            
            WHERE 
                lnkp."@СвязьДокументов" = ANY($1::integer[])
            ORDER BY lk2."@СвязьДокументов" DESC
        )
        
        SELECT DISTINCT ON (T0."@СвязьДокументов") T0.* FROM T0
   """
    result2 = sbis.SqlQuery(sql, points)
    result2.AddColInt32("ПеренесенВ.Документ")
    result2.AddColString("ПеренесенВ.Описание")
    result2.AddColDate("ПеренесенВ.ПланДатаНач")
    result2.AddColDate("ПеренесенВ.ПланДатаКнц")
    for r_p in result2:
        id_lk = r_p.Get("@СвязьДокументов", None)
        if id_lk in dict_name.keys():
            name_plan = dict_name[id_lk]
            r_p["ПеренесенВ.Документ"] = name_plan[1]
            r_p["ПеренесенВ.Описание"] = name_plan[0]
            r_p["ПеренесенВ.ПланДатаНач"] = name_plan[2]
            r_p["ПеренесенВ.ПланДатаКнц"] = name_plan[3]
            dict_name.pop(id_lk)

    for id_lk in dict_name.keys():
        for r_p in result:
            if id_lk == r_p.Get("@СвязьДокументов", None):
                result2.AddRow(r_p)

    return result2


def get_plan_point_access_right(points, plan_id):
    """
        Метод, возвращающий руководителей заказчика плана,
        руководителей заказчиков пунктов плана и кому переназначена папка
        :param points: (List) Список идентификаторов пунктов плана
        :param plan_id: Идентификатор плана работ
        :return:
        """
    leaders = {}
    surrogates = {}
    empl_leaders = {}
    empl_surrogates = {}

    rec_format = sbis.CreateRecordFormat()
    rec_format.AddInt64("@СвязьДокументов")
    rec_format.AddArrayInt64("РП.РуководителиЗакПлана")
    rec_format.AddArrayInt64("РП.РуководителиОтветственногоЗаПлан")
    rec_format.AddArrayInt64("РП.РуководителиЗакПунктаПлана")
    rec_format.AddArrayInt64("РП.РуководителиОтветственногоЗаПункт")
    rec_format.AddArrayInt64("РП.ПраваНаПапкуЗакПлана")
    rec_format.AddArrayInt64("РП.ПраваНаПапкуОтветственногоЗаПлан")
    rec_format.AddArrayInt64("РП.ПраваНаПапкуЗакПунктаПлана")
    rec_format.AddArrayInt64("РП.ПраваНаПапкуОтветственногоЗаПункт")

    rec_format.AddInt64("ДокументОснование.Лицо3")
    rec_format.AddInt64("ДокументОснование.Сотрудник")
    rec_format.AddInt64("СвязьДокументовПлан.Заказчик")
    rec_format.AddInt64("СвязьДокументовПлан.Исполнитель")
    rec_format.AddInt64("ДокументОснование.ЛицоСоздал")
    rec_format.AddInt64("РП.Права")
    rec_format.AddBool("РП.РазрешеноРедактировать")
    result = sbis.CreateRecordSet(rec_format)

    right = sbis.ПроверкаПрав.НаличиеДействия("Планы работ")
    allow_edit = sbis.Документ.РазрешеноЛиРедактироватьДокументВФазе(plan_id, right == 2)

    for rec in points:
        result_rec = sbis.CreateRecord(rec_format)
        # найдём руководителей заказчика плана
        result_rec["@СвязьДокументов"] = rec.Get("@СвязьДокументов", None)
        result_rec["ДокументОснование.ЛицоСоздал"] = rec.Get("ДокументОснование.ЛицоСоздал", None)
        plan_customer = rec.Get("ДокументОснование.Лицо3", None)
        result_rec["ДокументОснование.Лицо3"] = plan_customer
        person = int(plan_customer) if plan_customer else None
        if person is not None:
            if not person in leaders.keys():
                leaders[person] = []
                staff_heads = sbis.Персонал.РуководителиСотрудника(person)
                for l_rec in staff_heads:
                    leaders[person].append(int(l_rec.Get("@Лицо", None)))
            result_rec["РП.РуководителиЗакПлана"] = leaders[person]
        else:
            result_rec["РП.РуководителиЗакПлана"] = []

        # найдём руководителей ответственного за план
        plan_resp = rec.Get("ДокументОснование.Сотрудник", None)
        result_rec["ДокументОснование.Сотрудник"] = plan_resp
        person = int(plan_resp) if plan_resp else None
        if person is not None:
            if not person in leaders.keys():
                leaders[person] = []
                staff_heads = sbis.Персонал.РуководителиСотрудника(person)
                for l_rec in staff_heads:
                    leaders[person].append(int(l_rec.Get("@Лицо", None)))
            result_rec["РП.РуководителиОтветственногоЗаПлан"] = leaders[person]
        else:
            result_rec["РП.РуководителиОтветственногоЗаПлан"] = []

        # найдём руководителей заказчика пункта плана
        point_cust = rec.Get("СвязьДокументовПлан.Заказчик", None)
        result_rec["СвязьДокументовПлан.Заказчик"] = point_cust
        person = int(point_cust) if point_cust else None
        if person is not None:
            if not person in leaders.keys():
                leaders[person] = []
                staff_heads = sbis.Персонал.РуководителиСотрудника(person)
                for l_rec in staff_heads:
                    leaders[person].append(int(l_rec.Get("@Лицо", None)))
            result_rec["РП.РуководителиЗакПунктаПлана"] = leaders[person]
        else:
            result_rec["РП.РуководителиЗакПунктаПлана"] = []

        # найдём руководителей ответственного за пункта плана
        point_resp = rec.Get("СвязьДокументовПлан.Исполнитель", None)
        result_rec["СвязьДокументовПлан.Исполнитель"] = point_resp
        person = int(point_resp) if point_resp else None
        if person is not None:
            if not person in empl_leaders.keys():
                empl_leaders[person] = []
                staff_heads = sbis.Персонал.РуководителиСотрудника(person)
                for l_rec in staff_heads:
                    empl_leaders[person].append(int(l_rec.Get("@Лицо", None)))
            result_rec["РП.РуководителиОтветственногоЗаПункт"] = empl_leaders[person]
        else:
            result_rec["РП.РуководителиОтветственногоЗаПункт"] = []

        # найдём кому переназначена папка заказчика плана (пока без рекурсии)
        person = int(plan_customer) if plan_customer else None
        if person is not None:
            if not person in surrogates.keys():
                sur = TasksTransfer.getTransferredFace(person)
                if sur is not None:
                    surrogates[person] = [sur]
                else:
                    surrogates[person] = []
            result_rec["РП.ПраваНаПапкуЗакПлана"] = surrogates[person]
        else:
            result_rec["РП.ПраваНаПапкуЗакПлана"] = []

        # найдём кому переназначена папка ответственного за план (пока без рекурсии)
        person = int(plan_resp) if plan_resp else None
        if person is not None:
            if not person in surrogates.keys():
                sur = TasksTransfer.getTransferredFace(person)
                if sur is not None:
                    surrogates[person] = [sur]
                else:
                    surrogates[person] = []
            result_rec["РП.ПраваНаПапкуОтветственногоЗаПлан"] = surrogates[person]
        else:
            result_rec["РП.ПраваНаПапкуОтветственногоЗаПлан"] = []

        # найдём кому переназначена папка заказчика пункта плана (пока без рекурсии)
        person = int(point_cust) if point_cust else None
        if person is not None:
            if not person in surrogates.keys():
                sur = TasksTransfer.getTransferredFace(person)
                if sur is not None:
                    surrogates[person] = [sur]
                else:
                    surrogates[person] = []
            result_rec["РП.ПраваНаПапкуЗакПунктаПлана"] = surrogates[person]
        else:
            result_rec["РП.ПраваНаПапкуЗакПунктаПлана"] = []

        # найдём кому переназначена папка ответственного за пункта плана (пока без рекурсии)
        person = int(point_resp) if point_resp else None
        if person is not None:
            if not person in empl_surrogates.keys():
                sur = TasksTransfer.getTransferredFace(person)
                if sur is not None:
                    empl_surrogates[person] = [sur]
                else:
                    empl_surrogates[person] = []
            result_rec["РП.ПраваНаПапкуОтветственногоЗаПункт"] = empl_surrogates[person]
        else:
            result_rec["РП.ПраваНаПапкуОтветственногоЗаПункт"] = []

        result_rec["РП.Права"] = right
        result_rec["РП.РазрешеноРедактировать"] = allow_edit

        result.AddRow(result_rec)

    return result


def get_project_bread_crumbs(_filter):
    """         
    Метод извлечения данных для компонента 'хлебных крошек'
    отображающего положение проекта в иерархии папок        
    :param _filter: (Record) содержит поле идентификатора документа проекта
    :return:         
    """
    DOC_TYPES = get_doc_types()
    ptype = DOC_TYPES.Проект

    sql = """ WITH RECURSIVE T1 as (
            SELECT "@Документ", "Раздел", "Раздел@", "ТипДокумента", 1 as "Уровень",
					  'Проект' as name
            FROM "Документ"
            WHERE "ТипДокумента" = {ptype} AND "@Документ" = $1::integer

            UNION

            SELECT doc."@Документ", doc."Раздел", doc."Раздел@", doc."ТипДокумента",
               T1."Уровень" + 1 as "Уровень", exp_doc."Название"
            FROM T1
            INNER JOIN "Документ" doc
               ON T1."Раздел" = doc."@Документ"
			    INNER JOIN "ДокументРасширение" exp_doc
            ON doc."@Документ"=exp_doc."@Документ"
   	      WHERE doc."ТипДокумента" = {ptype} 
   	   )
       SELECT T1."@Документ", T1.name
       FROM T1
	     WHERE "Уровень" > 1
       ORDER BY "Уровень" DESC """.format(ptype=ptype)

    result = sbis.SqlQuery(sql, _filter.Get("ИдПроекта", None) if _filter.Get("ИдПроекта", None) else 0)

    return result


def get_plan_point_time_resources(points, start_date, end_date):
    """
        Метод возвращающий фактически затраченное
        время по документам в плане работ в минутах
        :param points: (RecordSet) набор документов с их исполнителями
        :param start_date: (Date) дата начала плана работ
        :param end_date: (Date) дата окончания плана работ
        :return:
        """
    format_ = sbis.CreateRecordFormat()
    format_.AddInt64("Документ")
    format_.AddInt64("ВремяМинут")
    result_rs = sbis.CreateRecordSet(format_)

    dict_face_doc = {}
    docs = []
    faces = []
    # перебор всех документов для получения входных
    # параметров функции get_plan_works а также подготовка
    # вспомогательных массивов данных
    for point in points:
        point_faces = point.Get("Исполнители", None)
        doc_id = point.Get("Документ", None)
        if doc_id:
            docs.append(doc_id)
        for point_face in point_faces:
            if point_face not in faces:
                faces.append(point_face)
            if point_face in dict_face_doc.keys():
                if doc_id not in dict_face_doc[point_face]:
                    dict_face_doc[point_face].append(doc_id)
            else:
                dict_face_doc[point_face] = [doc_id]

    if docs and dict_face_doc:
        # извлечение данных по временным затратам в период плана
        # по всем документам и исполнителям
        recs = get_plan_works(start_date, end_date, dict_face_doc, docs)
        # формирование словаря затраченного времени по документам

        result_rec = sbis.CreateRecord(format_)

        for doc in docs:
            time = 0
            for rec in recs:
                rec_doc = rec.Get("Документ", None)
                if rec.Get("ПрочиеРаботыМинут", None) is None and rec_doc == doc:
                    time += rec.Get("ВремяМинут", 0)

            result_rec["Документ"] = doc
            result_rec["ВремяМинут"] = time
            result_rs.AddRow(result_rec)

    return result_rs


def works_empl_for_period_and_doc(_filter):
    """
    Получает все работы сотрудников за период
    по указанному документу
    :param _filter: (Record) запись с параметрами запроса перечня работ
    :return: RecordSet
    """
	
    format = sbis.MethodResultFormat('СвязьДокументовПлан.РаботыСотрудниковПоПунктуПланаПлитка', 4)
    result_rs = sbis.CreateRecordSet(format)

    empl_ids = _filter.Get("ИдСотрудников", None)
    doc_id = _filter.Get("Документ", None)
    date_st = _filter.Get("ДатаНач", None)
    date_fn = _filter.Get("ДатаКнц", None)

    if not empl_ids or not date_st or not date_fn or not doc_id:
        return result_rs

    sql_works = """ WITH T AS (SELECT
                      wkr."@Работа",
                      wkr."Документ",
                      wkr."Дата",
                      wkr."ЧастноеЛицо",
                      wkr."Примечание",
                      extract(epoch FROM cast(("ВремяКнц" + justify_hours((cast(coalesce("ЧасовойПояс", '0') AS float)::text || ' hours')::interval))::time -
                                             ("ВремяНач" + justify_hours((cast(coalesce("ЧасовойПояс", '0') AS float)::text || ' hours')::interval))::time AS time))/60  AS "ВремяМинут"
                    FROM "Работа" wkr
                    WHERE      wkr."Удалена"  IS NOT True
                        AND wkr."Документ" = $2::integer				
                        AND (wkr."Тип" IS NULL OR wkr."Тип" = 0)
                        AND wkr."ВремяНач" IS NOT NULL
                        AND wkr."ВремяКнц" IS NOT NULL
                        AND ((lower(trim(wkr."Примечание")) != 'плановый отпуск')
                            OR (wkr."Примечание" IS NULL))
                        AND wkr."Дата" BETWEEN $3::date AND $4::date
                        AND wkr."ЧастноеЛицо" = ANY($1::integer[]) )
                SELECT T.*,coalesce("ЧастноеЛицо"."Фамилия", '') ||' '||left(coalesce("ЧастноеЛицо"."Имя", ''),1)||'.'||left(coalesce("ЧастноеЛицо"."Отчество", ''),1)||'.' AS "ЧастноеЛицо.Лицо.Название",
                    TO_CHAR(T."Дата", 'DD.MM.YY')  AS "ДатаСтрока",		   
                    TO_CHAR((cast(T."ВремяМинут" as text)||' minute')::interval, 'HH24:MI') AS "ВремяСтрока"
                FROM T INNER JOIN "ЧастноеЛицо"
                    ON "ЧастноеЛицо"."@Лицо" = T."ЧастноеЛицо"	"""
    works_recs = sbis.SqlQuery(sql_works, empl_ids, doc_id, date_st, date_fn)

    return works_recs
