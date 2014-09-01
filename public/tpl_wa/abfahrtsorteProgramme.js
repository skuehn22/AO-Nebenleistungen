Ext.namespace('abfahrtsorteProgramme');

abfahrtsorteProgramme = function(){

    var programmeGridStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/datensatz/tabelitems/",
        id: 'programmeGridStoreJson',
        fields: ['Fa_Id','progname','AO_City','company','cityId','prioNoko']
    });

    var cityStore = new Ext.data.JsonStore({
        root: 'data',
        method: 'post',
        url: "/admin/datensatz/getcities/",
        id: 'cities',
        fields: ['id','city'],
        autoLoad: true
    });

    function rendererAktivProgramm(val){
		if(val == '1' || val == '2')
			return "<img src='/buttons/accept.png' ext:qtip='Firma ist aktiv geschaltet'>";
		else
			return "<img src='/buttons/cancel.png' ext:qtip='Firma ist passiv geschaltet'>";
	}

    return{
        panel: new Ext.Panel({
            title: 'vorhandene Programme',
            width: 900,
            autoHeight: true
        }),

        gridProgramme: new Ext.grid.GridPanel({
			autoHeight: true,
	        stripeRows: true,
	        loadMask: true,
	        columnLines: true,
			autoExpandColumn: 'progname',
            border: false,
            viewConfig: {
                forceFit: true,
                scrollOffset: 0
            },
            listeners: {
                rowclick: function(){
                    var selected = this.getSelectionModel().getSelected();
                    programmId = selected.data.Fa_Id;
                }
            },
	        store: programmeGridStore,
	        width: 900,
	        id: 'gridProgrammeVorhanden',
		    columns: [{
		                xtype: 'numbercolumn',
		                dataIndex: 'Fa_Id',
		                header: 'Programm ID',
		                sortable: true,
		                width: 100,
		                format: '0',
		                id: 'Fa_Id'
		            },{
		                xtype: 'gridcolumn',
		                header: 'Programmname',
		                sortable: true,
		                dataIndex: 'progname',
			            id: 'progname'
		            },{
		                xtype: 'gridcolumn',
		                header: 'Stadt',
		                sortable: true,
		                width: 150,
		                dataIndex: 'AO_City',
			            id: 'AO_City'
		     		},{
		                xtype: 'gridcolumn',
		                header: 'Firma',
		                sortable: true,
		                width: 250,
		                dataIndex: 'company',
			            id: 'company'
		     		},{
		                xtype: 'gridcolumn',
		                header: 'cityId',
		                hidden: true,
		                width: 50,
		                dataIndex: 'cityId',
			            id: 'cityId'
		     		},{
						xtype: 'gridcolumn',
						header: 'aktiv',
						width: 50,
						dataIndex: 'prioNoko',
						id: 'prioNoko',
						renderer: rendererAktivProgramm
			     	}],
		    bbar: [{
                xtype: 'paging',
                store: programmeGridStore,
                id: 'paging',
                pageSize: 20,
                displayMsg: "Anzeige: {0} - {1} von {2} ",
                displayInfo: true
		    }],
            tbar: [{
					text: 'Programmbezeichnung :'
		        },{
					xtype: 'textfield',
					width: 150,
					name: 'progSearch',
					id: 'progSearch'
			    },{
					xtype: 'tbseparator'
				},{
					text: 'Stadt :'
				},{
					xtype: 'combo',
	                width: 150,
	                id: 'cityCombo',
	                forceSelection: true,
					triggerAction: 'all',
					displayField: 'city',
					valueField: 'id',
                    selectOnFocus: true,
					hiddenName: 'city',
                    mode: 'local',
					store: cityStore,
					typeAhead: true
				},{
					xtype: 'tbseparator'
				},{
					text: 'Firma:'
				},{
					xtype: 'textfield',
					width: 150,
					name: 'companySearch',
					id: 'companySearch'
				},{
					xtype: 'tbseparator'
				},{
					xtype: 'button',
					text: 'suche',
					icon: '/buttons/arrow_right.png',
					handler: function(){
						var progSearch = Ext.getCmp('progSearch').getValue();
						var city = Ext.getCmp('cityCombo').getValue();
                        var companySearch = Ext.getCmp('companySearch').getValue();

						programmeGridStore.setBaseParam('progsearch', progSearch);
						programmeGridStore.setBaseParam('city', city);
                        programmeGridStore.setBaseParam('company', companySearch);
                        
						programmeGridStore.load();
					}
				},{
					xtype: 'tbseparator'
			}]
        }),

        workAdmin: function(){
            programmeGridStore.load();
        }
    }
}

function abfahrtsorteProgrammeAdmin(){
    var view = new abfahrtsorteProgramme();
    view.workAdmin();
    view.panel.add(view.gridProgramme);
    view.panel.render('app');
}

function abfahrtsorteProgrammeRedakteur(){
    
}
