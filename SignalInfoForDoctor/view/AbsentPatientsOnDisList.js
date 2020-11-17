/**
 * Вкладка "Список неявившихся на диспансерном учете"
 * в сигнальной информации для врачей поликлиники
 *
 * PromedWeb - The New Generation of Medical Statistic Software
 * http://swan.perm.ru/PromedWeb
 *
 * AbsentPatientsOnDisList
 * @package      Polka
 * @access       public
 * @copyright    Copyright (c) 2019 Swan Ltd.
 */

Ext6.define('common.EMK.SignalInfoForDoctor.view.AbsentPatientsOnDisList', {
	extend: 'swPanel',
	alias: 'widget.AbsentPatientsOnDisList',

	/**
	 * @var Ссылка на контроллер вида вкладки
	 */
	controller: 'AbsentPatientsOnDisListController',

	region: 'center',
	border: false,
	layout: 'border',

	title: langs('Неявившиеся на диспансерном учете'),

	/**
	 * @var SignalInfoForDoctorForm Ссылка на форму сигнальной информации,
	 * где расположена вкладка
	 */
	ownerPanel: undefined,

	/**
	 * Поля фильтра
	 */
	LpuFilterCombo: undefined,
	OnDisSurnameFilter: undefined,
	OnDisFirstNameFilter: undefined,
	DisDateFilter: undefined,

	/**
	 * Грид, его стор и модель
	 */
	grid: undefined,
	gridStore: undefined,
	gridModel: undefined,

	/**
	 *  Передает входящие параметры запроса компоненту
	 **/
	setParams: function (params) {
		let me = this;
		me.Person_id = params.Person_id;
		me.Server_id = params.Server_id;
		me.userMedStaffFact = params.userMedStaffFact;
	},

	/**
	 *  Инициализирует компонент
	 **/
	initComponent: function () {
		let me = this,
			filterDefaults = {
				border: false,
				labelAlign: 'top',
				padding: '0 20'
			},
			panelDefaults = {
				border: false,
				bodyBorder: false,
				bodyStyle: 'background-color: #f9f9f9; border: 0;'
			};

		/**
		 * @var Ext6.data.Model Объект модели таблицы
		 */
		me.gridModel = new Ext6.create('Ext6.data.Model', {
			fields: [
				{name: 'Person_Fio', type: 'string'},
				{name: 'Person_BirthDay', type: 'date'},
				{name: 'Person_Age', type: 'date'},
				{name: 'Diag_Name', type: 'string'},
				{name: 'Lpu_Name', type: 'string'},
				{name: 'PersonDispVizit_NextDate', type: 'date'}
			]
		});

		/**
		 * @var Ext6.data.Store Объект связи таблицы с хранилищем
		 */
		me.gridStore = new Ext6.data.Store({
			model: me.gridModel,
			autoLoad: false,
			paging: true,
			useEmptyRecord: true,
			pageSize: 100, // TAG: постраничный вывод

			proxy: {
				type: 'ajax',
				url: '/?c=SignalInfo&m=loadAbsentPatientOnDis',

				actionMethods: {
					create: "POST",
					read: "POST",
					update: "POST",
					destroy: "POST"
				},

				reader: {
					type: 'json',
					rootProperty: 'data'
				}
			},

			sorters: [
				'EvnPS_setDate'
			]
		});

		/**
		 * Функция поиска в гриде по ФИО
		 */
		let byNameGridSearchFn = function (delay) {
			let _this = this;

			if (this.delaySearchId) {
				clearTimeout(this.delaySearchId);
			}

			me.delaySearchId = setTimeout(function () {
					if (me.paging) {
						me.params.fio = _this.value.toUpperCase();
						me.grid.getStore().getProxy().setExtraParams(me.params);
						me.grid.getStore().load();
					} else {
						me.grid.store.addFilter(function (rec) {
							let s = rec.get('Person_Fio'),
								pos = s.toUpperCase().search(_this.value.toUpperCase());

							return (pos >= 0);
						});
					}

					_this.delaySearchId = null;
				},
				delay);

			if (me.grid.store.filters.length) {
				me.grid.store.clearFilter();
			}
		};

		let byDiagGridSearchFn = function (delay) {
			let _this = this;

			if (this.delaySearchId) {
				clearTimeout(this.delaySearchId);
			}

			me.delaySearchId = setTimeout(function () {
					if (me.paging) {
						me.params.fio = _this.value.toUpperCase();
						me.grid.getStore().getProxy().setExtraParams(me.params);
						me.grid.getStore().load();
					} else {
						me.grid.store.addFilter(function (rec) {
							let s = rec.get('Diag_Name'),
								pos = s.toUpperCase().search(_this.value.toUpperCase());

							return (pos >= 0);
						});
					}

					_this.delaySearchId = null;
				},
				delay);

			if (me.grid.store.filters.length) {
				me.grid.store.clearFilter();
			}
		};

		/**
		 * Описание столбцов грида
		 */
		let gridColumns = [
			// ФИО (заголовок заменен на поле поиска):
			{
				dataIndex: 'Person_Fio',
				text: '',
				width: 250,
				minWidth: 150,
				maxWidth: 380,
				tdCls: 'fio-column',

				filter: {
					xtype: 'textfield',
					itemId: 'fiofilter',
					cls: 'remote-monitor-fiofilter',
					emptyText: langs('ФИО'),
					padding: '0 10 5 10',
					anchor: '-30',
					enableKeyEvents: true,
					delaySearchId: null,

					refreshTrigger: function () {
						let isEmpty = Ext6.isEmpty(this.getValue());
						this.triggers.clear.setVisible(!isEmpty);
						this.triggers.search.setVisible(isEmpty);
					},

					delaySearch: byNameGridSearchFn,

					triggers: {
						search: {
							cls: 'x6-form-search-trigger'
						},

						clear: {
							cls: 'x6-form-clear-trigger',
							hidden: true,

							handler: function () {
								this.setValue('');
								me.grid.store.clearFilter();
								this.refreshTrigger();
							}
						}
					},

					listeners: {
						keyup: function (field, e) {
							this.refreshTrigger();
							this.delaySearch(300);
						}
					}
				}
			},
			{
				header: langs('Дата рождения'),
				dataIndex: 'Person_BirthDay',
				width: 110
			},
			{
				header: langs('Возраст'),
				dataIndex: 'Person_Age',
				width: 70
			},
			{
				header: langs('МО госпитализации'),
				dataIndex: 'Lpu_Name',
				width: 250
			},
			{
				header: langs('Основной диагноз'),
				dataIndex: 'Diag_Name',
				width: 350,

				filter: {
					xtype: 'textfield',
					itemId: 'diagfilter',
					cls: 'remote-monitor-diagfilter',
					emptyText: langs('Основной диагноз'),
					padding: '0 10 5 10',
					anchor: '-30',
					enableKeyEvents: true,
					delaySearchId: null,

					refreshTrigger: function () {
						let isEmpty = Ext6.isEmpty(this.getValue());
						this.triggers.clear.setVisible(!isEmpty);
						this.triggers.search.setVisible(isEmpty);
					},

					delaySearch: byDiagGridSearchFn,

					triggers: {
						search: {
							cls: 'x6-form-search-trigger'
						},

						clear: {
							cls: 'x6-form-clear-trigger',
							hidden: true,

							handler: function () {
								this.setValue('');
								me.grid.store.clearFilter();
								this.refreshTrigger();
							}
						}
					},

					listeners: {
						keyup: function (field, e) {
							this.refreshTrigger();
							this.delaySearch(300);
						}
					}
				}
			},
			{
				header: langs('Дата назначенной явки'),
				dataIndex: 'PersonDispVizit_NextDate',
				width: 110
			}
		];

		/**
		 * Объект панели тулбара
		 */
		let toolBarPanel = {
			xtype: 'toolbar',
			width: '100%',

			defaults: {
				margin: '0 4 0 0',
				padding: '4 10',
				style: 'background-color: #ededed;'
			},

			layout: {
				type: 'hbox',
				pack: 'end'
			},

			items: [
				{
					itemId: 'btnOpenEMK',
					text: langs('Открыть ЭМК'),
					margin: '0 0 0 6',
					disabled: true,
					iconCls: 'action_openemk',
					handler: 'openPersonEmkWindow'
				},
				{
					itemId: 'btnRefresh',
					text: langs('Обновить'),
					iconCls: 'action_refresh',
					handler: 'doSearch'
				},
				{
					itemId: 'btnOpenEvnPS',
					text: langs('Открыть КВС'),
					disabled: true,
					handler: 'openEvnPSEditWindow'
				},
				{
					itemId: 'btnPrint',
					text: langs('Печать'),
					iconCls: 'action_print',

					menu: [
						{
							itemId: 'singlePrint',
							text: langs('Печать'),
							disabled: true,

							handler: function () {
								let params = {},
									rec = me.grid.getSelectionModel()
										.getSelected().items[0];

								params.EvnPS_id = rec.get('EvnPS_id');
								params.EvnSection_id = rec.get('EvnSection_id');
								params.LpuUnitType_SysNick = 'stac';
								params.KVS_Type = 'AB';
								printEvnPS(params);
							}
						},
						{
							itemId: 'pagePrint',
							text: langs('Печать текущей страницы'),

							handler: function () {
								Ext6.ux.GridPrinter.print(me.grid);
							}
						},
						{
							itemId: 'listPrint',
							text: langs('Печать всего списка'),

							handler: function () {
								Ext6.ux.GridPrinter.print(me.grid);
							}
						}
					]
				}
			]
		};

		/**
		 * Объект нижней панели под фильтром
		 */
		let bottomGridPanel = {
			width: '100%',
			height: 66,
			bodyPadding: 20,

			layout: {
				type: 'hbox',
				pack: 'end'
			},

			defaults: {
				xtype: 'button',
				width: 100
			},

			items: [
				{
					text: langs('Найти'),
					cls: 'button-primary',
					margin: '0 0 20 0',
					handler: 'doSearch'
				},
				{
					text: langs('Сбросить'),
					cls: 'button-secondary',
					margin: '0 0 20 9',
					handler: this._clearFilter,
					scope: this
				}
			]
		};

		/**
		 * Фильтр по МО
		 */
		let lpuFilter = {
			xtype: 'swLpuCombo',
			itemId: 'lpuFilter',
			name: 'Lpu_id',
			anyMatch: true,
			hideEmptyRow: true,
			fieldLabel: langs('МО госпитализации'),
			emptyText: langs('Поиск по МО госпитализации'),

			additionalRecord: {
				value: -1,
				text: langs('Все'),
				code: 0
			},

			listConfig: {
				minWidth: 500
			}
		};

		/**
		 * Фильтр по дате назначенной явки
		 * @type {Ext6.date.RangeField}
		 */
		let absentDateFilter = Ext6.create('Ext6.date.RangeField',
			Object.assign({},
				filterDefaults,
				{
					name: 'DisDateFilter_period',
					itemId: 'disDateFilter',
					filterByValue: true,
					fieldLabel: langs('Дата назначенной явки'),
					emptyText: langs('Поиск по дате назначенной явки'),
					minDate: new Date().add(Date.DAY, -11),
					value: new Date().add(Date.DAY, -1)
				}));

		/**
		 * Объект вертикальной панели
		 * с основным фильтром
		 */
		let mainFilter = {
			xtype: 'panel',
			region: 'west',
			title: langs('Фильтр'),
			collapsible: true,
			split: true,
			width: 500,
			defaults: panelDefaults,

			layout: {
				type: 'vbox',
				align: 'stretch'
			},

			header: {
				padding: '9 0 9 20'
			},

			items: [
				// Верхняя часть - с полями фильтра
				{
					defaults: filterDefaults,

					layout: {
						type: 'vbox',
						align: 'stretch'
					},

					items: [
						// Фамилия:
						{
							xtype: 'textfield',
							itemId: 'OnDisSurnameFilter',
							name: 'Person_Surname',
							type: 'int',
							filterByValue: true,
							hideLabel: false,
							fieldLabel: langs('Фамилия'),
							emptyText: langs('Поиск по фамилии')
						},
						// Имя:
						{
							xtype: 'textfield',
							itemId: 'OnDisFirstNameFilter',
							name: 'Person_Firname',
							type: 'int',
							filterByValue: true,
							hideLabel: false,
							fieldLabel: langs('Имя'),
							emptyText: langs('Поиск по имени')
						},
						// МО госпитализации:
						lpuFilter,

						//Фильтр по дате назначенной явки
						absentDateFilter
					]
				},

				// Нижняя часть - все оставшееся место, внизу панель с кнопками "Найти" и "Сбросить"
				{
					flex: 1,
					defaults: panelDefaults,

					layout: {
						type: 'vbox',
						pack: 'end'
					},

					items: [
						// Нижняя панель с кнопками "Найти" и "Сбросить":
						bottomGridPanel
					]
				}
			]
		};

		/**
		 * Размещаемый объект вкладки
		 */
		Ext6.apply(me, {
			defaults: panelDefaults,

			items: [
				// Фильтр:
				mainFilter,

				// Грид:
				{
					xtype: 'grid',
					region: 'center',
					itemId: 'grid',
					scrollable: true,
					minHeight: 500,
					cls: 'grid-common',
					style: 'border-left: 1px solid silver;',
					bodyStyle: 'background-color: #fff;',

					store: me.gridStore,

					requires: [
						'Ext6.ux.GridHeaderFilters'
					],

					plugins: [
						Ext6.create('Ext6.grid.filters.Filters', {
							showMenu: false
						}),

						Ext6.create('Ext6.ux.GridHeaderFilters', {
							enableTooltip: false,
							reloadOnChange: false
						})
					],

					// Панель инструментов:
					tbar: toolBarPanel,

					// Столбцы:
					columns: gridColumns,

					selModel: {
						mode: 'SINGLE',
						headerWidth: 40,

						listeners: {
							select: function (model, record, index) {
								me.getController().setActiveButtonsState(true);
							},

							deselect: function (model, record, index) {
								me.getController().setActiveButtonsState(false);
							}
						}
					}
				}
			]
		});

		me.callParent(arguments);

		me.grid = me.down('#grid');
		me.LpuFilterCombo = me.down('#lpuFilter');
		me.OnDisSurnameFilter = me.down('#OnDisSurnameFilter');
		me.OnDisFirstNameFilter = me.down('#OnDisFirstNameFilter');
		me.DisDateFilter = me.down('#disDateFilter');
	},

	/**
	 *  Очищает поля фильтра и устанавливает в поле "Дата выписки" значение по умолчанию - вчерашнюю дату.
	 *  @returns null
	 **/
	_clearFilter: function () {
		if (this.LpuFilterCombo) {
			this.LpuFilterCombo.clearValue();
		}

		if (this.OnDisSurnameFilter) {
			this.OnDisSurnameFilter.cleanupField();
		}

		if (this.OnDisFirstNameFilter) {
			this.OnDisFirstNameFilter.cleanupField();
		}

		if (this.DisDateFilter) {
			this.DisDateFilter.clear();
			this.DisDateFilter.setDates(new Date().add(Date.DAY, -1))
		}

	}
});