Ext6.define('common.EMK.SignalInfoForDoctor.controller.DisFromHospitalController', {
	extend: 'Ext6.app.ViewController',
	alias: 'controller.DisFromHospitalController',
	openPersonEmkWindow: function() {
		var me = this,
			view = me.getView();
		if (getWnd('swPersonEmkWindow').isVisible() || getWnd('swPersonEmkWindowExt6').isVisible())
		{
			sw.swMsg.alert(langs('Сообщение'), langs('Окно редактирования ЭМК уже открыто'));
			return false;
		}
		
		if (view.grid.getSelectionModel().hasSelection()) {
			var record = view.grid.getSelectionModel().getSelection()[0];
			if (typeof record != 'object' || Ext6.isEmpty(record.get('Person_id'))) return false;
			if(getGlobalOptions().client == 'ext2') {
				getWnd('swPersonEmkWindow').show({
					Person_id: record.get('Person_id'),
					ARMType: 'common',
					readOnly: false,
					callback: function ()
					{
						view.Person_id=record.get('Person_id');
					}.createDelegate(this)
				});
			} else {
				getWnd('swPersonEmkWindowExt6').show({
					Person_id: record.get('Person_id'),
					Server_id: record.get('Server_id'),
					PersonEvn_id: record.get('PersonEvn_id'),
					closeToolText: 'Закрыть',
					userMedStaffFact: view.userMedStaffFact,
					MedStaffFact_id: getGlobalOptions().CurMedStaffFact_id,
					LpuSection_id: getGlobalOptions().CurLpuSection_id,
					TimetableGraf_id: null,
					EvnDirectionData: null,
					ARMType: view.userMedStaffFact.ARMType,
					callback: function(retParams) {
						view.Person_id=record.get('Person_id');
					}
				});
			}
		}
	},
	//Метод, вызывающий форму редактирования КВС
	openEvnPSEditWindow: function()
	{
		var me = this,
			view = me.getView(),
			record = view.grid.getSelectionModel().getSelection()[0];

		if (getWnd('swEvnPSEditWindow').isVisible())
		{
			sw.swMsg.alert(langs('Сообщение'), langs('Окно редактирования карты выбывшего из стационара уже открыто'));
			return false;
		}

		var params = {
			EvnPS_id: record.get('EvnPS_id'),
			userMedStaffFact: view.userMedStaffFact,
			Person_id: record.get('Person_id'),
			Server_id: record.get('Server_id'),
			action: 'view'
		};
		getWnd('swEvnPSEditWindow').show(params);
	},
	setActiveButtonsState: function(state) {
		var me = this,
			view = me.getView();
		if(state) {
			view.queryById('singlePrint').enable();
			view.queryById('btnOpenEMK').enable();
			view.queryById('btnOpenEvnPS').enable();
			return;
		}
		view.queryById('singlePrint').disable();
		view.queryById('btnOpenEMK').disable();
		view.queryById('btnOpenEvnPS').disable();
	},
	doSearch: function() {
		var me = this,
			view = me.getView(),
			disDateFilterForm = view.DisDateFilter,
			lpuFilterForm = view.LpuFilterCombo,
			medFilterForm = view.MedServiceFilterCombo,
			evnpsFilterForm = view.EvnPSNumCardFilter,
			hospitDateFilterForm = view.HospitDateFilter,
			params = {};
		params.start = 0;
		params.limit = 100;
		if (!disDateFilterForm.isValid() ) return this.warning();
		params.PregnancyRouteType = 'DisHospital';
		params.EvnPS_NumCard = evnpsFilterForm.getValue();
		params.Lpu_id = null;
		params.LpuSection_id = null;
		params.MedService_id = null;
		if(lpuFilterForm.getValue() != -1) params.Lpu_id = lpuFilterForm.getValue() ? lpuFilterForm.getValue() : view.userMedStaffFact.Lpu_id;
		if(medFilterForm.getValue() != -1) params.LpuSection_id = medFilterForm.getValue() ? medFilterForm.getValue() : view.userMedStaffFact.LpuSection_id;
		if(medFilterForm.getValue() != -1) params.MedService_id = medFilterForm.getValue() ? medFilterForm.getValue() : view.userMedStaffFact.MedService_id;
		params.DisDateFrom = disDateFilterForm.getDateFrom() ? disDateFilterForm.getDateFrom().toString('dd.MM.yyyy') : null;
		params.DisDateTo = disDateFilterForm.getDateTo() ? disDateFilterForm.getDateTo().toString('dd.MM.yyyy') : null;
		params.HospitDateFrom = hospitDateFilterForm.getDateFrom() ? hospitDateFilterForm.getDateFrom().toString('dd.MM.yyyy') : null;
		params.HospitDateTo = hospitDateFilterForm.getDateTo() ? hospitDateFilterForm.getDateTo().toString('dd.MM.yyyy') : null;
		view.gridStore.removeAll();
		view.gridStore.load({params: params});
	},
});