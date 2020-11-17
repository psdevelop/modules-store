/**
 * Вкладка "Выписанные из стационара" в сигнальной информации для врачей поликлиники
 *
 * PromedWeb - The New Generation of Medical Statistic Software
 * http://swan.perm.ru/PromedWeb
 *
 * SignalInfoForDoctorForm
 * @package      Polka
 * @access       public
 * @copyright    Copyright (c) 2019 Swan Ltd.
 *
 */
Ext6.define('common.EMK.SignalInfoForDoctor.view.DisFromHospital', {
	extend: 'swPanel',
	alias: 'widget.DisFromHospital',
	
	controller: 'DisFromHospitalController',
	
	region: 'center',
	border: false,
	layout: 'border',
	
	title: langs('Выписанные из стационара'),
	
	// Ссылка на форму сигнальной информации, где расположена вкладка:
	ownerPanel: undefined,
	
	// Поля фильтра:
	LpuFilterCombo: undefined,
	MedServiceFilterCombo: undefined,
	EvnPSNumCardFilter: undefined,
	DisDateFilter: undefined,
	HospitDateFilter: undefined,

	// Грид, его стор и модель:
	grid: undefined,
	gridStore: undefined,
	gridModel: undefined,

	afterShow: function() {
		this.getController().doSearch();
	},

	/******************************************************************************************************************
	 *  setParams
	 ******************************************************************************************************************/
	setParams: function(params) {
		var me = this;
		me.Person_id = params.Person_id;
		me.Server_id = params.Server_id;
		me.userMedStaffFact = params.userMedStaffFact;
	},

	/******************************************************************************************************************
	 *  initComponent
	 ******************************************************************************************************************/
	initComponent: function() {
		var me = this,
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
		
		// Создаем модель:
		me.gridModel = new Ext6.create('Ext6.data.Model', {
			fields: [
				{ name: 'Person_Fio', type: 'string' },
				{ name: 'Person_BirthDay', type: 'date' },
				{ name: 'Person_Age', type: 'date' },
				{ name: 'EvnPS_setDate', type: 'date' },
				{ name: 'Diag_Name', type: 'string' },
				{ name: 'Lpu_Name', type: 'string' },
				{ name: 'LpuSection_Name', type: 'string' },
				{ name: 'PrehospType_Name', type: 'string' },
				{ name: 'EvnPS_NumCard', type: 'int' },
				{ name: 'EvnPS_disDate', type: 'date' }
			]
		});

		// Создаем стор:
		me.gridStore = new Ext6.data.Store({
			model: me.gridModel,
			autoLoad: false,
			paging: true,
			useEmptyRecord: true,
			pageSize: 100, // TAG: постраничный вывод
			
			proxy: {
				type: 'ajax',
				url: '/?c=SignalInfo&m=loadFromStac',
				
				actionMethods: {
					create: "POST",
					read: "POST",
					update: "POST",
					destroy: "POST"
				},
				
				reader:  {
					type: 'json',
					rootProperty: 'data'
				}
			},
			
			sorters: [
				'EvnPS_setDate'
			]
		});

		// Размещаем компоненты:
		Ext6.apply(me, {
			defaults: panelDefaults,
			
			items: [
				// Фильтр:
				{
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
								// МО госпитализации:
								{
									xtype: 'swLpuCombo',
									itemId: 'lpuFilter',
									name: 'Lpu_id',
									anyMatch: true,
									hideEmptyRow: true,
									matchFieldWidth: true,
									fieldLabel: langs('МО госпитализации'),
									emptyText: langs('Поиск по МО госпитализации'),

									additionalRecord: {
										value: -1,
										text: langs('Все'),
										code: 0
									},

									listeners: {
										'change': function(combo, newValue, oldValue) {
											me.MedServiceFilterCombo.getStore().proxy.extraParams.ARMType = me.userMedStaffFact.ARMType;

											if (newValue > 0) {
												me.MedServiceFilterCombo.getStore().proxy.extraParams.Lpu_id = newValue;
												me.MedServiceFilterCombo.getStore().proxy.extraParams.Lpu_isAll = 0;
											} else {
												me.MedServiceFilterCombo.getStore().proxy.extraParams.Lpu_id = null;
												me.MedServiceFilterCombo.getStore().proxy.extraParams.Lpu_isAll = 1;
											}

											me.MedServiceFilterCombo.setValue(-1);
											me.MedServiceFilterCombo.getStore().load({
												callback: function() {
													me.MedServiceFilterCombo.setValue(-1);
												}
											});
										}
									}
								},

								// Отделение госпитализации:
								{
									xtype: 'swMedServiceCombo',
									itemId: 'medServiceFilter',
									name: 'MedService_id',
									anyMatch: true,
									hideEmptyRow: true,
									queryMode: 'local',
									matchFieldWidth: true,
									fieldLabel: langs('Отделение госпитализации'),
									emptyText: langs('Поиск по отделению госпитализации'),

									additionalRecord: {
										value: -1,
										text: langs('Все'),
										code: 0
									},

									needDisplayLpu: function() {
										return me.LpuFilterCombo.getValue() == -1;
									}
								},

								// Номер КВС:
								{
									xtype: 'textfield',
									itemId: 'evnPSNumCardFilter',
									name: 'EvnPS_NumCard',
									filterByValue: true,
									hideLabel: false,
									fieldLabel: langs('Номер КВС'),
									emptyText: langs('Поиск по КВС')
								},

								// Дата выписки:
								Ext6.create('Ext6.date.RangeField',
									Object.assign({},
										filterDefaults,
										{
											name: 'DisDateFilter_period',
											itemId: 'disDateFilter',
											filterByValue: true,
											fieldLabel: langs('Дата выписки'),
											emptyText: langs('Поиск по дате выписки'),
											minDate: new Date().add(Date.DAY, -11),
											value: new Date().add(Date.DAY, -1)
										})),

								// Дата госпитализации:
								Ext6.create('Ext6.date.RangeField',
									Object.assign({},
										filterDefaults,
										{
											name: 'HospitDateFilter_period',
											itemId: 'hospitDateFilter',
											filterByValue: true,
											fieldLabel: langs('Дата госпитализации'),
											emptyText: langs('Поиск по дате госпитализации')
										}))
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
								{
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
								}
							]
						}
					]
				},

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
					tbar: {
						xtype: 'toolbar',
						width: '100%',
						cls: 'grid-toolbar',
						
						defaults: {
							margin: '0 4 0 0',
							padding: '4 10'
						},
						
						layout: {
							type: 'hbox',
							pack: 'end'
						},

						items: [
							{
								itemId: 'btnOpenEMK',
								text: langs('Открыть ЭМК'),
								tooltip: 'Открыть электронную медицинскую карту пациента',
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
								tooltip: langs('Открыть карту выбывшего из стационара'),
								iconCls : 'action_openevnps',
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

										handler: function() {
											var params = {},
												rec = me.grid.getSelectionModel().getSelected().items[0];

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

										handler: function() {
											Ext6.ux.GridPrinter.print(me.grid);
										}
									},
									{
										itemId: 'listPrint',
										text: langs('Печать всего списка'),

										handler: function() {
											Ext6.ux.GridPrinter.print(me.grid);
										}
									}
								]
							}
						]
					},

					// Столбцы:
					columns: [
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
								// cls: 'remote-monitor-fiofilter',
								emptyText: langs('ФИО'),
								padding: '0 0 5 0',
								anchor: '-30',
								minHeight: 10,
								enableKeyEvents: true,
								delaySearchId: null,

								refreshTrigger: function() {
									var isEmpty = Ext6.isEmpty(this.getValue());
									this.triggers.clear.setVisible(!isEmpty);
									this.triggers.search.setVisible(isEmpty);
								},

								delaySearch: function(delay) {
									var _this = this;

									if (this.delaySearchId)
										clearTimeout(this.delaySearchId);

									me.delaySearchId = setTimeout(function() {
										if (me.paging) {
											me.params.fio = _this.value.toUpperCase();
											me.grid.getStore().getProxy().setExtraParams(me.params);
											me.grid.getStore().load();
										} else {
											me.grid.store.addFilter(function(rec) {
												var s = rec.get('Person_Fio');
												pos = s.toUpperCase().search(_this.value.toUpperCase());

												if (pos >= 0)
													return true;
												else
													return false;
											});
										}

										_this.delaySearchId = null;
									},
									delay);

									if (me.grid.store.filters.length)
										me.grid.store.clearFilter();
								},

								triggers: {
									search: {
										cls: 'x6-form-search-trigger'
									},

									clear: {
										cls: 'x6-form-clear-trigger',
										hidden: true,

										handler: function() {
											this.setValue('');
											me.grid.store.clearFilter();
											this.refreshTrigger();
										}
									}
								},

								listeners: {
									keyup: function(field, e) {
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
							header: langs('Дата госпитализации'),
							dataIndex: 'EvnPS_setDate',
							width: 110
						},
						{
							header: langs('Основной диагноз'),
							dataIndex: 'Diag_Name',
							width: 350
						},
						{
							header: langs('МО госпитализации'),
							dataIndex: 'Lpu_Name',
							width: 250
						},
						{
							header: langs('Отделение'),
							dataIndex: 'LpuSection_Name',
							width: 250
						},
						{
							header: langs('Тип госпитализации'),
							dataIndex: 'PrehospType_Name',
							width: 140
						},
						{
							header: langs('Номер КВС'),
							dataIndex: 'EvnPS_NumCard',
							width: 110
						},
						{
							header: langs('Дата выписки'),
							dataIndex: 'EvnPS_disDate',
							flex: 1,
							minWidth: 110
						}
					],
					
					selModel: {
						mode: 'SINGLE',
						allowDeselect: true,

						listeners: {
							selectionchange: function(model, selection, eOpts) {
								me.getController().setActiveButtonsState(selection.length == 1);
							}
						}
					}
				}
			]
		});

		me.callParent(arguments);

		me.grid = me.down('#grid');
		me.LpuFilterCombo = me.down('#lpuFilter'); 
		me.MedServiceFilterCombo = me.down('#medServiceFilter');
		me.EvnPSNumCardFilter = me.down('#evnPSNumCardFilter');
		me.DisDateFilter = me.down('#disDateFilter');
		me.HospitDateFilter = me.down('#hospitDateFilter');
	},

	/******************************************************************************************************************
	 *  _clearFilter
	 *  
	 *  Очистка полей фильтра и установка в поле "Дата выписки" значения по умолчанию - вчерашней даты.
	 ******************************************************************************************************************/
	_clearFilter: function() {
		if (this.LpuFilterCombo)
			this.LpuFilterCombo.clearValue();
		
		if (this.MedServiceFilterCombo)
			this.MedServiceFilterCombo.clearValue();
		
		if (this.EvnPSNumCardFilter)
			this.EvnPSNumCardFilter.setValue(null);
		
		if (this.DisDateFilter) {
			this.DisDateFilter.clear();
			this.DisDateFilter.setDates(new Date().add(Date.DAY, -1))
		};
		
		if (this.HospitDateFilter)
			this.HospitDateFilter.clear();
	},

	/******************************************************************************************************************
	 *  _clearTab
	 *
	 *  Очистка фильтра и грида.
	 ******************************************************************************************************************/
	_clearTab: function() {
		this._clearFilter();
		this.gridStore.removeAll();
	}	
});