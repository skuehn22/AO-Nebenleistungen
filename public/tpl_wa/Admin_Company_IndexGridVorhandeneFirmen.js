Ext.onReady(function(){

    storeGridCompany = new Ext.data.JsonStore({
        id: 'storeGridCompany',
        root: 'data',
        url: '/admin/company/getcompanies/',
        totalProperty: 'anzahl',
        method: 'post',
        autoLoad: true,
        fields: [{
            name: 'id'
        },{
            name: 'company_name'
        },{
            name: 'aktiv'
        }]
    });

    gridCompany = new Ext.grid.GridPanel({
        title: 'vorhandene Firmen',
        store: storeGridCompany,
        width: 400,
        autoHeight: true,
        stripeRows: true,
        loadMask: true,
        columnLines: true,
        autoExpandColumn: 'company_name',
        renderTo: 'panelGridCompany',
        id: 'gridCompany',
        viewConfig: {
            forceFit: true,
            scrollOffset: 0
        },
        listeners: {
            rowdblclick: getVorhandenerProgrammanbieter,
            rowclick: function(grid, rowIndex, event){
                var row = grid.getSelectionModel().getSelected();
                memoryCompanyId = row.data.id;
                memoryCompanyName = row.data.company_name;

                var test = 123;
            }
        },
        columns: [{
            xtype: 'gridcolumn',
            dataIndex: 'id',
            header: 'Id',
            width: 50,
            id: 'idCompany'
        },{
            xtype: 'gridcolumn',
            dataIndex: 'company_name',
            header: 'Firmenname',
            width: 150,
            id: 'company_name'
        },{
            xtype: 'gridcolumn',
            dataIndex: 'aktiv',
            header: 'aktiv',
            width: 50,
            id: 'aktiv',
            renderer: renderer_aktiv
        }],
        bbar: [{
            xtype: 'paging',
            store: storeGridCompany,
            id: 'pagingCompanyGrid',
            pageSize: 20,
            displayMsg: "Anzeige: {0} - {1} von {2} ",
            displayInfo: true
        }],
        tbar: [{
            xtype: 'tbspacer',
            width: 20
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'tbtext',
            text: 'Firma: '
        },{
            xtype: 'textfield',
            id: 'suchFeldFirma'
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'tbbutton',
            text: 'suchen',
            icon: '/buttons/arrow_right.png',
            handler: function(){
                var suchparameterFirma = Ext.getCmp('suchFeldFirma').getValue();
                storeGridCompany.load({
                    params: {
                        sucheFirma: suchparameterFirma
                    }
                });

                return;
            }
        },{
            xtype: 'tbseparator'
        }]
    });

});