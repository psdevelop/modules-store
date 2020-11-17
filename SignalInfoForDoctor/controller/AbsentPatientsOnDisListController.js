/**
 * Контроллер вкладки "Список неявившихся на диспансерном учете" в сигнальной
 * информации для врачей поликлиники
 *
 * PromedWeb - The New Generation of Medical Statistic Software
 * http://swan.perm.ru/PromedWeb
 *
 * AbsentPatientsOnDisListController
 * @package      Polka
 * @access       public
 * @copyright    Copyright (c) 2019 Swan Ltd.
 */
Ext6.define('common.EMK.SignalInfoForDoctor.controller.AbsentPatientsOnDisListController', {
	extend: 'Ext6.app.ViewController',
	alias: 'controller.AbsentPatientsOnDisListController',

	/**
	 * Открывает электронную мед. карточку
	 * @returns {boolean|null}
	 */
	openPersonEmkWindow: function () {
		let me = this,
			view = me.getView();
		if (getWnd('swPersonEmkWindow').isVisible() || getWnd('swPersonEmkWindowExt6').isVisible()) {
			sw.swMsg.alert(langs('Сообщение'), langs('Окно редактирования ЭМК уже открыто'));
			return false;
		}

		let selectionModel = view.grid.getSelectionModel()
		if (!selectionModel.hasSelection()) {
			return false;
		}

		let record = selectionModel.getSelection()[0];
		if (typeof record != 'object' || Ext6.isEmpty(record.get('Person_id'))) {
			return false;
		}

		let globalOptions = getGlobalOptions();
		if (globalOptions.client == 'ext2') {
			getWnd('swPersonEmkWindow').show({
				Person_id: record.get('Person_id'),
				ARMType: 'common',
				readOnly: false,
				callback: function () {
					view.Person_id = record.get('Person_id');
				}.createDelegate(this)
			});
		} else {
			getWnd('swPersonEmkWindowExt6').show({
				Person_id: record.get('Person_id'),
				Server_id: record.get('Server_id'),
				PersonEvn_id: record.get('PersonEvn_id'),
				closeToolText: 'Закрыть',
				userMedStaffFact: view.userMedStaffFact,
				MedStaffFact_id: globalOptions.CurMedStaffFact_id,
				LpuSection_id: globalOptions.CurLpuSection_id,
				TimetableGraf_id: null,
				EvnDirectionData: null,
				ARMType: view.userMedStaffFact.ARMType,
				callback: function (retParams) {
					view.Person_id = record.get('Person_id');
				}
			});
		}

	},

	/**
	 * Вызывает форму редактирования КВС
	 * @returns {boolean|null}
	 */
	openEvnPSEditWindow: function () {
		let me = this,
			view = me.getView(),
			record = view.grid.getSelectionModel().getSelection()[0];

		if (getWnd('swEvnPSEditWindow').isVisible()) {
			sw.swMsg.alert(langs('Сообщение'), langs('Окно редактирования карты выбывшего из стационара уже открыто'));
			return false;
		}

		let params = {
			EvnPS_id: record.get('EvnPS_id'),
			userMedStaffFact: view.userMedStaffFact,
			Person_id: record.get('Person_id'),
			Server_id: record.get('Server_id'),
			action: 'view'
		};
		getWnd('swEvnPSEditWindow').show(params);
	},

	/**
	 * Делает доступными/недоступными кнопки операций с объектом строки
	 * @param state boolean Признак активности кнопок
	 * @returns null
	 */
	setActiveButtonsState: function (state) {
		let me = this,
			view = me.getView(),
			buttonsIds = ['singlePrint', 'btnOpenEMK', 'btnOpenEvnPS'];

		if (state) {
			buttonsIds.forEach(function (item) {
				view.queryById(item).enable();
			});
			return null;
		}

		buttonsIds.forEach(function (item) {
			view.queryById(item).disable();
		});
		return null;
	},

	/**
	 * Отправляет запрос поиска и отправляет результат
	 * объекту таблицы Неявившиеся на диспансерном учете
	 * @returns {boolean|*}
	 */
	doSearch: function () {
		let me = this,
			view = me.getView(),
			disDateFilterForm = view.DisDateFilter;

		if (!disDateFilterForm.isValid()) {
			return this.warning();
		}

		let disDateFrom = disDateFilterForm.getDateFrom(),
			disDateTo = disDateFilterForm.getDateTo(),
			lpuFilterValue = view.LpuFilterCombo.getValue(),
			params = {};

		params.start = 0;
		params.limit = 100;

		params.PregnancyRouteType = 'DisHospital';
		params.Lpu_id = null;

		if (lpuFilterValue != -1) {
			params.Lpu_id = lpuFilterValue || view.userMedStaffFact.Lpu_id;
		}

		params.DisDateFrom = disDateFrom ? disDateFrom.toString('dd.MM.yyyy') : null;
		params.DisDateTo = disDateTo ? disDateTo.toString('dd.MM.yyyy') : null;

		params.surname = view.OnDisSurnameFilter.getValue();
		params.first_name = view.OnDisFirstNameFilter.getValue();

		view.gridStore.removeAll();
		view.gridStore.load({params: params});
	}
});