/**
 * Форма сигнальной информации для врачей поликлиники
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
Ext6.define('common.EMK.SignalInfoForDoctor.SignalInfoForDoctorForm', {
	extend: 'base.BaseForm',
	alias: 'widget.swSignalInfoForDoctorForm',
	itemId: 'SignalInfoForDoctorForm',
	
	requires: [
		'common.EMK.SignalInfoForDoctor.controller.MainController',
		'common.EMK.SignalInfoForDoctor.controller.AbsentPatientsOnDisListController',
		'common.EMK.SignalInfoForDoctor.controller.DisFromHospitalController',
		'common.EMK.SignalInfoForDoctor.controller.DisFromHospitalOnDisAccountController',
		'common.EMK.SignalInfoForDoctor.model.MainModel',
		'common.EMK.SignalInfoForDoctor.store.MainStore',
		'common.EMK.SignalInfoForDoctor.view.MainPanel',
		'common.EMK.SignalInfoForDoctor.view.AbsentPatientsOnDisList',
		'common.EMK.SignalInfoForDoctor.view.DisFromHospital',
		'common.EMK.SignalInfoForDoctor.view.DisFromHospitalOnDisAccount'
	],
	
	viewModel: 'SignalInfoForDoctorMainModel',
	controller: 'SignalInfoForDoctorMainController',
	
	constrain: true,
	maximized: true,
	closable: true,
	header: false,
	border: false,
	layout: 'border',
	
	title: langs('Сигнальная информация для врача'),
	closeToolText: langs('Закрыть'),

	findWindow: false,
	evnParams: {},
	params: {},
	lastUpdateData: [],
	userMedStaffFact: null,

	// Панель с вкладками, наполняется в зависимости от рабочего места:
	tabPanel: undefined,

	// Вкладка "Грид" (common.EMK.SignalInfoForDoctor.view.MainPanel):
	MainPanel: undefined,

	/**
	 *  @var absentPatientsOnDisList
	 *  common.EMK.SignalInfoForDoctor.view.AbsentPatientsOnDisList
	 *  Объект вкладки с таблицей неявившихся 
	 *  из состоящих на диспансерном учете
	 **/
	absentPatientsOnDisList: undefined,

	// Вкладка "Выписанные из стационара" (common.EMK.SignalInfoForDoctor.view.DisFromHospital):
	DisFromHospital: undefined,

	/** 
	 *  @var DisFromHospitalOnDisAccount
	 *  common.EMK.SignalInfoForDoctor.view.DisFromHospitalOnDisAccount
	 *  Объект вкладки с таблицей выписанных
	 *  из стационара, но состоящих на диспансерном учете
	 **/
	DisFromHospitalOnDisAccount: undefined,

	addCodeRefresh: Ext6.emptyFn,
	getParams: 'getParams',

	/******************************************************************************************************************
	 *  setParams
	 *  Используется в ЭМК
	 ******************************************************************************************************************/
	setParams: function(params) {//используется в ЭМК
		this.getController().setParams(params);
	},
	
	/******************************************************************************************************************
	 *  getMainStore
	 ******************************************************************************************************************/
	getMainStore: function () {
		let gridStore = this.MainPanel && this.MainPanel.MainGrid 
			&& this.MainPanel.MainGrid.getStore();
		return gridStore || false;
	},

	/******************************************************************************************************************
	 *  loadData
	 *  Используется в ЭМК
	 ******************************************************************************************************************/
	loadData: function(options) {//используется в ЭМК
		this.getController().loadData(options);

		let me = this;
		let vm = me.getViewModel();
		let SignalInfoForDoctorForm_id = vm.get('SignalInfoForDoctorForm_id');
	},

	/******************************************************************************************************************
	 *  show
	 ******************************************************************************************************************/
	show: function(params) {
		let me = this,
			umsFact = params.userMedStaffFact;
		me.userMedStaffFact = umsFact;
		this.absentPatientsOnDisList.userMedStaffFact = umsFact;
		this.DisFromHospital.userMedStaffFact = umsFact;
		this.DisFromHospitalOnDisAccount.userMedStaffFact = umsFact;
		this.getController().updateTabs(me);

		me.callParent(arguments);
	},

	/******************************************************************************************************************
	 *  initComponent
	 ******************************************************************************************************************/
	initComponent: function() {
		let me = this;
		
		me.toolPanel = Ext6.create('Ext6.Toolbar', {
			region: 'east',
			height: 40,
			border: false,
			noWrap: true,
			right: 0,
			style: 'background: transparent;',
			items: [
				{
					xtype: 'tbspacer',
					width: 10
				}
			]
		});
		
		me.titlePanel = Ext6.create('Ext6.Panel', {
			region: 'north',
			style: {
				'box-shadow': '0px 1px 6px 2px #ccc',
				zIndex: 2
			},
			layout: 'border',
			border: false,
			height: 40,
			bodyStyle: 'background-color: #EEEEEE;',
			items: [
				me.toolPanel
			],
			xtype: 'panel'
		});

		// Создадим вкладки по умолчанию:
		// 1. Грид:
		me.MainPanel = Ext6.create('common.EMK.SignalInfoForDoctor.view.MainPanel', {
			ownerPanel: me,
			itemId: 'MainPanel',
			collapseOnOnlyTitle: true,
			collapsed: true,
		});

		// 2. Выписанные из стационара:
		me.DisFromHospital = Ext6.create('common.EMK.SignalInfoForDoctor.view.DisFromHospital', {
			ownerPanel: me,
			itemId: 'DisFromHospitalPanel',
			collapsed: false
		});

		/**
		 * Выписанные из стационара на диспансерном наблюдении
		 * @type {common.EMK.SignalInfoForDoctor.view.DisFromHospitalOnDisAccount}
		 */
		me.DisFromHospitalOnDisAccount = Ext6.create(
			'common.EMK.SignalInfoForDoctor.view.DisFromHospitalOnDisAccount', {
				ownerPanel: me,
				itemId: 'DisFromHospitalOnDisAccountPanel',
				collapsed: false
			});

		/**
		 * Список неявившихся из состоящих на диспансерном учете
		 * @type {common.EMK.SignalInfoForDoctor.view.AbsentPatientsOnDisList}
		 */
		me.absentPatientsOnDisList = Ext6.create(
			'common.EMK.SignalInfoForDoctor.view.AbsentPatientsOnDisList', {
				ownerPanel: me,
				itemId: 'AbsentPatientsOnDisListPanel',
				collapsed: false
			});

		Ext6.apply(me, {
			cls: 'signal-info',
			
			defaults: {
				border: false,
				padding: 0
			},
			
			items: [
				{
					region: 'center',
					scrollable: true,
					layout: 'fit',

					items: [
						{
							xtype: 'tabpanel',
							itemId: 'tabPanel',
							
							header: {
								style: 'border: 1px solid silver; background-color: #fff;',
								titleRotation: 0,
								padding: 0,
								
								title: {
									text: "Разделы",
									flex: 0,
									height: 38,
									width: '100%',
									style: 'background-color: #ededed;',
									textAlign: 'left',
									padding: '10 20',
									margin: 0
								}
							},
							
							headerPosition : 'left',
							tabBarHeaderPosition: 1,
							tabRotation: 0,
							activeTab: null,

							tabBar: {
								cls: 'white-tab-bar',
								margin: 0,
								
								defaults: {
									cls: 'simple-tab',
									padding: '10 20',
									textAlign: 'left'
								}
							},
								
							items: [
								me.MainPanel,
								{
									title: 'Параклинические услуги',
									border: false,
								},
								{
									title: 'Вызовы СМП',
									border: false,
								},
								{
									title: 'Регистры льготников',
									border: false,
								},
								{
									title: 'Медицинские свидетельства о смерти',
									border: false,
								},
								me.absentPatientsOnDisList,
								me.DisFromHospital,
								me.DisFromHospitalOnDisAccount
							]
						}
					]
				}
			]
		});

		me.callParent(arguments);

		me.tabPanel = me.down('#tabPanel');
	}
});