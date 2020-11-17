Ext6.define('common.EMK.SignalInfoForDoctor.view.MainPanel', {
	extend: 'swPanel',
	requires: [
		'common.EMK.SignalInfoForDoctor.controller.MainController',
		'common.EMK.SignalInfoForDoctor.store.MainStore',
	],
	alias: 'widget.SignalInfoForDoctor_MainPanel',
	userCls: 'panel-with-tree-dots accordion-panel-window',
	title: 'Грид',
	controller: 'SignalInfoForDoctorMainController',
	ownerPanel: {},
	bodyPadding: 10,
	getField: function(name) {
		return this.MainForm.getForm().findField(name);
	},
	getGrid: function() {
		return this.MainGrid;
	},
	initComponent: function() {
		var me = this;
		
		// me.MainGrid = Ext6.create('Ext6.grid.Panel', {//таблица согласий/услуг
		// 	xtype: 'cell-editing',
		// 	cls: 'grid-common',
		// 	xtype: 'grid',
		// 	region: 'center',
		// 	border: false,
		// 	viewConfig:{
		// 		markDirty:false,
		// 		getRowClass: function (record, rowIndex) {
		// 			//~ var c = record.get('DopDispInfoConsent_IsImpossible');
		// 			//~ if (c == 2) {
		// 				//~ return 'x-item-disabled';
		// 			//~ } else return '';
		// 		}
		// 	},
		// 	bind: {
		// 		store: '{MainStore}'
		// 	},
		// 	columns: [
		// 		'id'
		// 	]
		// });
		
		me.MainForm = Ext6.create('Ext6.form.Panel', {
			accessType: 'view',
			padding: "18 0 30 27",
			layout: 'anchor',
			//~ bodyPadding: 10,
			border: false,
			defaults: {
				anchor: '100%'
			},
			items: [{
				layout: 'column',
				border: false,
				items: [
					//me.MainGrid
				]
			}]
		});
			
		Ext6.apply(me, {
			items: [
				me.MainForm
			]
		});
			
		this.callParent(arguments);
	}
});
	