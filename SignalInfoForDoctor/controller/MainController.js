Ext6.define('common.EMK.SignalInfoForDoctor.controller.MainController', {
	extend: 'Ext6.app.ViewController',
	alias: 'controller.SignalInfoForDoctorMainController',
	updateTabs: function (me) {
		var tabs,
			i;
		
		me.DisFromHospital = Ext6.create('common.EMK.SignalInfoForDoctor.view.DisFromHospital', {
			ownerPanel: me,
			itemId: 'DisFromHospitalPanel',
			collapsed: false
		});
		me.DisFromHospital.userMedStaffFact = me.userMedStaffFact;

		if (me.userMedStaffFact.PostMed_id.inlist(['73','74','75','76','111','112','117','119','10170','10171',
			'10172','10173','10527','10528','10529','10530','10990','10991','10992'])) {
			me.tabPanel.removeAll();
			me.tabPanel.add([
				{
					title: 'Параклинические услуги',
					border: false,
				},
				{
					title: 'Вызовы СМП',
					border: false,
				},
				me.DisFromHospital,
				{
					title: 'Регистр льготников',
					border: false,
				},
				{
					title: 'Открытые ЛВН',
					border: false,
				},
				{
					title: 'Медицинские свидетельства о смерти',
					border: false,
				},
				{
					title: 'Список неявившихся',
					border: false,
				},
				{
					title: 'Диспансеризация',
					border: false,
				}
			]);
		} else if   (me.userMedStaffFact.PostMed_Name.inlist(['Врач-акушер-гинеколог','Акушер'
			,'Заведующий фельдшерско-акушерским пунктом - акушер'])) {
			me.tabPanel.removeAll();
			me.tabPanel.add([
				{
					title: 'Беременные женщины',
					border: false,
				},
				{
					title: 'Вызовы СМП',
					border: false,
				},
				{
					title: 'Находятся на госпитализации',
					border: false,
				},
				{
					title: 'Выписанные из стационара',
					border: false,
				},
			]);
		}

		// Очистим вкладки, имеющиеся на панели, при помощи определенных в них функций _clearTab:
		for (i = 0, tabs = me.tabPanel.items; i < tabs.length; i ++)
			if ((tab = tabs.getAt(i)) && (typeof tab._clearTab == 'function'))
				tab._clearTab();
	}
});