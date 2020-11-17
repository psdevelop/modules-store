Ext6.define('SignalInfoForDoctorMainStoreModel', {
	extend: 'Ext6.data.Model',
	alias: 'model.SignalInfoForDoctorMainStoreModel',
	idProperty: 'id',
	fields: [
		{ name: 'id', type: 'int'},
	]
});

Ext6.define('common.EMK.SignalInfoForDoctor.store.MainStore', {
	extend: 'Ext6.data.Store',
	alias: 'store.SignalInfoForDoctorMainStore',
	model: 'SignalInfoForDoctorMainStoreModel',
	proxy: {
		type: 'ajax',
		actionMethods:  {create: "POST", read: "POST", update: "POST", destroy: "POST"},
		url: '',
		reader: {
			type: 'json',
			rootProperty: 'data'
		},
		extraParams: '{mainExtraParams}'
	},
	fields: [
		{ name: 'id', type: 'int'},
	],
	sorters: [
		'id'
	]
});