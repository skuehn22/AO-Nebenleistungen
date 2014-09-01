var offeneFragenStore = new Ext.data.JsonStore({
    storeId: 'offeneFragenStore',
    url: '/admin/whiteboard/offenekommentare/',
    root: 'data',
    totalProperty: 'anzahl',
    autoLoad: true,
    method: 'post',
    fields: [{
            mapping: 'id',
            name: 'id'
        },{
            mapping: 'company',
            name: 'company'
        },{
            mapping: 'progname',
            name: 'progname'
        },{
            mapping: 'kommentar',
            name: 'kommentar'
        }]
});

function whiteboardFragenAdmin(){

    var offeneKommentareGrid = new Ext.grid.GridPanel({
        autoHeight: true,
        width: 700,
        id: 'offeneKommentareExternerRedakteure',
        renderTo: 'offeneFragen',
        title: 'offene Fragen',
        autoExpandColumn: 'zusatzinformation',
        columnLines: true,
        loadMask: true,
        store: offeneFragenStore,
        stripeRows: true,
        viewConfig: {
            scrollOffset: 0,
            forceFit: true
        },
        listeners: {
            rowdblclick: function(){
                kommentarAnzeigen();
            }
        },
        columns: [{
            xtype: 'gridcolumn',
            dataIndex: 'id',
            header: 'ID Kommentar',
            width: 50
        },{
            xtype: 'gridcolumn',
            dataIndex: 'company',
            header: 'Firma',
            width: 100
        },{
            xtype: 'gridcolumn',
            dataIndex: 'progname',
            header: 'Programm Name',
            width: 100
        },{
            xtype: 'gridcolumn',
            dataIndex: 'kommentar',
            header: 'Kommentar',
            width: 100,
            renderer: kuerzeKommentar
        }],
        bbar: {
            xtype: 'paging',
            displayInfo: true,
            pageSize: 10,
            store: offeneFragenStore
        },
        buttons: [{
            text: 'Kommentar als erledigt kennzeichnen',
            handler: kommentarErledigt
        },{
            text: 'Kommentar anzeigen',
            handler: kommentarAnzeigen
        }]
    });

    var Frage = new Ext.Panel({
        // frame: true,
        height: 261,
        width: 415,
        padding: 10,
        renderTo: 'einzelneFrage',
        title: 'Kommentar',
        items: [{
            xtype: 'textarea',
            disabled: true,
            id: 'inhaltDesKommentar',
            height: 196,
            width: 387
        }]
    });
}

// kürzt den Kommentar
function kuerzeKommentar(val){

    var kurzKommentar = val.substr(0,30);
    kurzKommentar = kurzKommentar + '...';

    return kurzKommentar;
}

// zeigt Kommentar in Panel an
function kommentarAnzeigen(){

    var grid = Ext.getCmp('offeneKommentareExternerRedakteure');

    if(!grid.getSelectionModel().hasSelection()){
        showMsgBox('Bitte ein Kommentar auswählen');

        return;
    }

    var frage = grid.getSelectionModel().getSelected();
    Ext.getCmp('inhaltDesKommentar').setValue(frage.data.kommentar);

    return;
}

// schaltet den Kommentar als erledigt
function kommentarErledigt(){
    var grid = Ext.getCmp('offeneKommentareExternerRedakteure');

    if(!grid.getSelectionModel().hasSelection()){
        showMsgBox('Bitte ein Kommentar auswählen');

        return;
    }

    var row = grid.getSelectionModel().getSelected();

    Ext.Ajax.request({
        url: '/admin/whiteboard/kommentarerledigt/',
        method: 'post',
        params: {
            programmId: row.data.id
        },
        success: function(){
            Ext.getCmp('offeneKommentareExternerRedakteure').store.load();

        }
        
    });
}



