var adminTroubleFehlerGrid = function()
{
    var statusSymbol = function(wert)
    {
        if(wert == 1)
            return "<img src='/buttons/cross.png' ext:qtip='Problem nicht behoben'>";
        else
            return "<img src='/buttons/accept.png' ext:qtip='Problem geklärt'>";
    }

    var gridFehlerJsonStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'daten',
        method: 'post',
        id: 'gridFehlerJsonStore',
        url: "/admin/trouble/get-fehler/",
        fields: ['id','buchungsnummer','date','lastname','firstname','status','kundenId']
    });

    var fehlerTabelle = new Ext.grid.GridPanel({
        id: 'fehlerTabelle',
        autoHeight: true,
        autoWidth: true,
        title: 'Buchungsfehler',
        columnLines: true,
        stripeRows: true,
        loadMask: true,
        store: gridFehlerJsonStore,
        // renderTo: 'fehlerGrid',
        viewConfig: {
           forceFit: true,
           scrollOffset: 0
        },
        autoExpandColumn: 'lastname',
        columns: [{
                xtype: 'gridcolumn',
                dataIndex: 'id',
                header: 'Id',
                sortable: false,
                name: 'id',
                resizable: false,
                width: 50
            },{
            xtype: 'gridcolumn',
                dataIndex: 'kundenId',
                name: 'kundenId',
                hidden: true
            },{
                xtype: 'gridcolumn',
                dataIndex: 'buchungsnummer',
                header: 'Buchungsnummer',
                name: 'buchungsnummer',
                sortable: false,
                resizable: false,
                width: 100
            },{
                xtype: 'gridcolumn',
                dataIndex: 'date',
                header: 'Datum / Zeit',
                sortable: false,
                resizable: false,
                width: 150
            },{
                xtype: 'gridcolumn',
                dataIndex: 'firstname',
                header: 'Vorname',
                sortable: false,
                resizable: false,
                width: 100
            },{
                xtype: 'gridcolumn',
                dataIndex: 'lastname',
                header: 'Name',
                sortable: false,
                resizable: false,
                width: 100
            },{
                xtype: 'gridcolumn',
                dataIndex: 'status',
                header: 'Status',
                sortable: false,
                resizable: false,
                width: 50,
                renderer: statusSymbol
            }],
            bbar: {
                xtype: 'paging',
                displayInfo: true,
                store: gridFehlerJsonStore,
                pageSize: 10
            },
            buttons: [{
                text: 'anzeigen',
                handler: function()
                {
                    if(!fehlerTabelle.getSelectionModel().hasSelection()){
                        showMsgBox('Bitte Buchungsproblen auswählen!');

                        return;
                    }

                    memoryBuchungsfehlerId = fehlerTabelle.getSelectionModel().getSelected().get('id');
                    memoryKundenId = fehlerTabelle.getSelectionModel().getSelected().get('kundenId');
                    memoryBuchungsnummerId = fehlerTabelle.getSelectionModel().getSelected().get('buchungsnummer');

                    memoryAdminTroubleKontakt.init();
                    memoryAdminTroubleHotelbuchung.init();
                }
            },{
                text: 'offen',
                handler: function(){
                    setStatusProblenbuchung(1);
                }
            },{
                text: 'erledigt',
                handler: function(){
                    setStatusProblenbuchung(2);
                }
            }]
        });

        function setStatusProblenbuchung(status)
        {
            if(!fehlerTabelle.getSelectionModel().hasSelection()){
                showMsgBox('Bitte Buchungsproblen auswählen!');

                return;
            }

            var row = fehlerTabelle.getSelectionModel().getSelected().set('status',status);
            var id = fehlerTabelle.getSelectionModel().getSelected().get('id');

            Ext.Ajax.request({
                url: '/admin/trouble/set-buchungsfehler-status',
                success: function(request){
                    showMsgBox('Status geändert');
                },
                params: {
                    id: id,
                    status: status
                }
            });
        }

    return{
        init: function(){
            fehlerTabelle.render('fehlerGrid');
            fehlerTabelle.show();
            gridFehlerJsonStore.load();
        }
    }
}

Ext.onReady(function(){
    var adminTroubleFehlerObject = new adminTroubleFehlerGrid();
    adminTroubleFehlerObject.init();
});
