var adminTroubleHotelbuchung = function(){

    var gridHotelbuchungJsonStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'daten',
        method: 'post',
        id: 'gridHotelbuchungJsonStore',
        url: "/admin/trouble/get-hotelbuchung/",
        fields: ['id','buchungsnummer','stadt','hotel','rate','zimmeranzahl','naechte','personen','zimmerpreis','anreise']
    });

    var hotelbuchungTabelle = new Ext.grid.GridPanel({
        id: 'hotelbuchungTabelle',
        autoHeight: true,
        width: 1075,
        title: 'Hotelbuchung',
        columnLines: true,
        stripeRows: true,
        loadMask: true,
        store: gridHotelbuchungJsonStore,
        viewConfig: {
           forceFit: true,
           scrollOffset: 0
        },
        autoExpandColumn: 'hotel',
        columns: [{
                xtype: 'gridcolumn',
                dataIndex: 'id',
                header: 'Id',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'buchungsnummer',
                header: 'Buchungsnummer',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'stadt',
                header: 'Stadt',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'hotel',
                header: 'Hotel',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'rate',
                header: 'Zimmertyp',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'zimmeranzahl',
                header: 'Zimmeranzahl',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'naechte',
                header: 'Nächte',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'personen',
                header: 'Personen',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'zimmerpreis',
                header: 'Zimmerpreis',
                sortable: false,
                resizable: false,
                width: 50
            },{
                xtype: 'gridcolumn',
                dataIndex: 'anreise',
                header: 'Anreise',
                sortable: false,
                resizable: false,
                width: 50
            }],
            bbar: {
                xtype: 'paging',
                displayInfo: true,
                store: gridHotelbuchungJsonStore,
                pageSize: 10
            }
        });

    return{
        init: function()
        {
            hotelbuchungTabelle.render('hotelbuchung');
            hotelbuchungTabelle.show();
            gridHotelbuchungJsonStore.setBaseParam('buchungsnummerId', memoryBuchungsnummerId);
            gridHotelbuchungJsonStore.load();
        }
    }
}

Ext.onReady(function(){
    memoryAdminTroubleHotelbuchung = new adminTroubleHotelbuchung();
});