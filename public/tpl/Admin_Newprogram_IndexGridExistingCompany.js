Ext.onReady(function(){

    jsonStoreGridCompany = new Ext.data.JsonStore({
        id: 'jsonStoreGridCompany',
        totalProperty: 'anzahl',
        root: 'data',
        url: '/admin/newprogram/getexistingcompanies/',
        fields: ['id','company','city','aktiv']
    });


    gridExistingCompany = new Ext.grid.GridPanel({
        title: 'Liste der vorhandenen Firmen',
        width: 400,
        autoHeight: true,
        autoExpandColumn: 'company',
        stripeRows: true,
        columnLines: true,
        store: jsonStoreGridCompany,
        id: 'gridExistingCompany',
        listeners: {
            rowdblclick: showFormNewProgram
        },
        viewConfig: {
            forceFit: true,
            scrollOffset: 0
        },
        columns: [{
            xtype: 'gridcolumn',
            dataIndex: 'id',
            header: 'Id',
            sortable: true,
            width: 50,
            editable: false,
            id: 'id'
        },{
            xtype: 'gridcolumn',
            dataIndex: 'company',
            header: 'Firma',
            sortable: true,
            width: 250,
            editable: false,
            id: 'company'
        },{
            xtype: 'gridcolumn',
            dataIndex: 'city',
            header: 'Stadt',
            sortable: true,
            width: 150,
            editable: false,
            id: 'city'
        },{
            xtype: 'gridcolumn',
            dataIndex: 'aktiv',
            header: 'aktiv',
            sortable: true,
            width: 50,
            editable: false,
            id: 'aktiv',
            renderer: rendererAktivPassivIcon
        }],
        bbar: {
            xtype: 'paging',
            store: jsonStoreGridCompany,
            displayInfo: true
        },
        tbar: {
            xtype: 'toolbar',
            id: 'formSearch',
            items: [{
                xtype: 'tbtext',
                text: 'Firma:'
            },{
                xtype: 'tbspacer',
                width: 10
            },{
                xtype: 'textfield',
                width: 100,
                id: 'companySearch',
                allowBlank: false,
                minLength: 3
            },{
                xtype: 'tbspacer',
                width: 10
            },{
                xtype: 'tbseparator'
            },{
                xtype: 'button',
                text: 'suchen',
                id: 'searchButton',
                listeners: {
                    click: sendQuestionFindCompany
                }
            },{
                xtype: 'tbseparator'
            }]
        },
        buttons: [{
            text: 'neues Programm',
            handler: showFormNewProgram
        },{
            text: 'vorhandene Programme der Firma',
            handler: showProgrammeDerFirma
        }]
    });

    gridExistingCompany.render('panelExistingCompany');
    jsonStoreGridCompany.load();
});