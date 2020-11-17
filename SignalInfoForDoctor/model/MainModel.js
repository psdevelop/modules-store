
Ext6.define('common.EMK.SignalInfoForDoctor.model.MainModel', {
	extend: 'Ext6.app.ViewModel',
	requires: [
		'common.EMK.SignalInfoForDoctor.store.MainStore'
	],
	alias: 'viewmodel.SignalInfoForDoctorMainModel',
	data: {
		//вспомогательные переменные:
		enableEdit: true,
		action: 'add',
	},
	reset: function() {
		var vm = this;
		vm.setData({

		});
	},
	setParams: function (params) {
		var vm = this;
		if (params) {
			vm.set('params', params);
		} else {
			//чтобы лишний раз не переприсваивать
			var p = vm.get('params');
		}
	},
	bindings: {
		onSignalInfoForDoctor_idChange: '{SignalInfoForDoctor_id}',
	},
	stores: {
		MainStore: {
			type: 'SignalInfoForDoctorMainStore',
			proxy: {
				extraParams: '{mainExtraParams}'
			},
			listeners: {
				load: 'onLoadMainGrid'
			}
		}
	}
});