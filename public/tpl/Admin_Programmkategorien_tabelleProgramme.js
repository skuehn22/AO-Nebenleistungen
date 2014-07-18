var tabelleProgrammeClass = function()
{
    var tabelleKategorienObj;

    function callTabelleKategorien()
    {
        var tabelle = Ext.getCmp("programmtabelle");
        var selectionModel = tabelle.getSelectionModel();

        if(selectionModel.hasSelection()){
            var row = selectionModel.getSelected();
            tabelleKategorienObj.kategorienErmitteln(row.data.programmId);
        }
        else
            showMsgBox('Bitte Programm wählen');
    }

    var gridTabelleProgrammeJsonStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/Programmkategorien/programmtabelle/",
        id: 'storeGridProgramme',
        fields: ['programmId','progname','city','cityId']
    });

    var tabelle = new Ext.grid.GridPanel({
        id: 'programmtabelle',
        autoHeight: true,
        width: 530,
        border: false,
        collapseFirst: false,
        title: 'vorhandene Programme',
        enableColumnHide: false,
        enableColumnMove: false,
        enableColumnResize: false,
        store: gridTabelleProgrammeJsonStore,
        renderTo: 'tabelleVorhandeneProgramme',
        autoExpandColumn: 'progname',
        columnLines: true,
        stripeRows: true,
        viewConfig: {
            forceFit: true,
            scrollOffset: 0
        },
        listeners: {
            dblclick: function(){
                callTabelleKategorien();
            }
        },
        columns: [{
            dataIndex: 'programmId',
            id: 'programmId',
            editable: false,
            groupable: false,
            hidden: false,
            header: 'ID Programm',
            resizable: false,
            sortable: true,
            width: 100
        },{
            dataIndex: 'progname',
            editable: false,
            groupable: false,
            header: 'Programmname',
            id: 'progname',
            hideable: false,
            resizable: false,
            sortable: true,
            width: 250
        },{
            dataIndex: 'city',
            editable: false,
            groupable: false,
            header: 'Stadt',
            hideable: false,
            resizable: false,
            sortable: true,
            width: 100
        },{
            dataIndex: 'cityId',
            hidden: true,
            width: 50
        }],
        bbar: {
            xtype: 'paging',
            displayInfo: true,
            store: gridTabelleProgrammeJsonStore,
            pageSize: 20
        },
        tbar:[{
            xtype: 'tbspacer',
            width: 20
        },{
            text: 'Stadt:'
        },{
            xtype: 'textfield',
            id: 'sucheCity',
            width: 150
        },{
            xtype: 'tbseparator'
        },{
            text: 'Programm :'
        },{
            xtype: 'textfield',
            width: 150,
            id: 'sucheProgramm'
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'button',
            text: 'suchen',
            tooltip: 'sucht nach den Programmen',
            icon: '/buttons/arrow_right.png',
            handler: function(){
                var sucheCity = Ext.getCmp('sucheCity').getValue();
                var sucheProgramm = Ext.getCmp('sucheProgramm').getValue();

                gridTabelleProgrammeJsonStore.setBaseParam('sucheCity',sucheCity);
                gridTabelleProgrammeJsonStore.setBaseParam('sucheProgramm', sucheProgramm);
                gridTabelleProgrammeJsonStore.load();
            }
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'tbspacer',
            width: 20
        }],
        buttons: [{
            text: 'Programmkategorien anzeigen',
            handler: function(){
                callTabelleKategorien();
            }
        }]
    });

    jsonStoreProgrammkategorien = new Ext.data.JsonStore({
        url: '/admin/datensatz/programmkategorien',
        root: 'data',
        method: 'post',
        idProperty: 'id',
        totalProperty: 'count',
        fields: ['id','datumSperrtag']
    });

    return{
        start: function(){
            return tabelle;
        },
        setTabelleKategorienObj: function(tabelleKategorien){
            tabelleKategorienObj = tabelleKategorien;
        }
    }
}
