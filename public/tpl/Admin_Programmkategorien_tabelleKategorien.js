var tabelleKategorienClass = function()
{
    var programmId = 0;

    function programmKategorienSpeichern()
    {
        var tabelle = Ext.getCmp('adminKategorientabelle');
        var rows = tabelle.selModel.getSelections();

        if(rows.length == 0){
            showMsgBox('Bitte Kategorien für das Programm auswählen');

            return;
        }

        kategorien = new Array();

        for(var i = 0; i < rows.length; i++){
            kategorien[i] = new Object();
            kategorien[i]['zaehler'] = rows[i].data.zaehler;
            kategorien[i]['prioritaet'] = rows[i].data.prioritaet;
        }

        Ext.Ajax.request({
            method: 'POST',
            url: '/admin/programmkategorien/speichern-kategorien/',
            params  : {
                programmId: programmId,
                kategorien: Ext.util.JSON.encode(kategorien)
            },
            success: function() {
                showMsgBox('Kategorien des Programm eingetragen');
            },
            failure: function() {
                showMsgBox('Fehler eintragen Kategorien des Programmes');
            }
        });



    }

    var gridTabelleKategorienJsonStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/Programmkategorien/ermitteln-kategorien/",
        id: 'storeGridProgramme',
        fields: ['zaehler','de','en','prioritaet']
    });

    // Checkbox Selection Model
    var checkboxselection = new Ext.grid.CheckboxSelectionModel({
        singleSelect: false,
        checkOnly: true
    });

    function rendererPrioritaet(prioritaet)
    {
        if(prioritaet == '1')
            return "<img src='/buttons/brick.png' ext:qtip='Programmkategorie normale Priorität'>";
        else
            return "<img src='/buttons/brick_add.png' ext:qtip='Programmkategorie erhöhte Priorität'>";
    }

    function triggerPrioritaet(grid, rowIndex, prioritaet, store, zaehler)
    {
        // wurde ein Programm ausgewählt ?
        if(programmId == 0){
            showMsgBox('Bitte Programm auswählen !');

            return;
        }

        var anzahl = store.getCount();

        for(var i = 0; i < anzahl; i++){
            store.getAt(i).set('prioritaet', 1);
        }

        var selectedRows = grid.getSelectionModel().getSelections();

        for(var j = 0; j < selectedRows.length; j++){
            if(zaehler == selectedRows[j].data.zaehler)
                store.getAt(rowIndex).set('prioritaet', 2);
        }

        return;
    }

    function checkedRows(programmId)
    {
        Ext.Ajax.request({
            method: 'POST',
            url: '/admin/programmkategorien/kategorien-programm/',
            params  : {
                programmId: programmId
            },
            success: function(xhr) {
                var tabelle = Ext.getCmp('adminKategorientabelle');
                var anzahlKategorien = tabelle.getStore().getCount();
                var store = tabelle.getStore();

                // make sure to decode for loadData to work
                var json = Ext.util.JSON.decode(xhr.responseText);

                if(!json.data){
                    showMsgBox('für das Programm wurden keine Kategorien vergeben');

                    return;
                }

                // Ausgangslage der Tabelle
                for(var i = 0; i < anzahlKategorien; i++){
                    store.getRange()[i].set('prioritaet',1);
                }

                // markieren Checkboxen
                selectedRows = new Array();

                for (var i = 0; i < anzahlKategorien; i++){

                    // ermitteln Zähler der Kategorie
                    var row = store.getAt(i);
                    var zaehler = row.data.zaehler;

                    for(var j = 0; j < json.data.length; j++){
                        var kategorieId = json.data[j].kategorieId;
                        var prioritaet = json.data[j].prioritaet;


                        if(zaehler == kategorieId){
                            // setzen checked in Array
                            selectedRows.push(i);

                            // setzen Prioritaet
                            store.getRange()[i].set('prioritaet', prioritaet);
                        }
                    }
                }

                tabelle.selModel.selectRows(selectedRows);

                // setzen Symbol 'brick'

            },
            failure: function() {
                showMsgBox('Fehler Kategorien ermitteln');
            }
        });
    }

    var tabelleKategorien = new Ext.grid.GridPanel({
        id: 'adminKategorientabelle',
        autoHeight: true,
        width: 450,
        border: false,
        collapseFirst: false,
        title: 'vorhandene Programmkategorien',
        enableColumnHide: false,
        enableColumnMove: false,
        enableColumnResize: false,
        store: gridTabelleKategorienJsonStore,
        renderTo: 'tabelleVorhandeneKategorien',
        autoExpandColumn: 'kategorieName',
        columnLines: true,
        stripeRows: true,
        selModel: checkboxselection,
        viewConfig: {
            forceFit: true,
            scrollOffset: 0,
            markDirty:false
        },
        listeners: {
            celldblclick: function(grid, rowIndex, columnIndex, event){
                var row = grid.getStore().getAt(rowIndex).data;
                var store = grid.getStore();
                var zaehler = row.zaehler;

                triggerPrioritaet(grid, rowIndex, row.prioritaet, store, zaehler);
            }
        },
        columns: [
            checkboxselection
            ,{
                dataIndex: 'zaehler',
                id: 'zaehler',
                editable: false,
                groupable: false,
                hidden: false,
                header: 'Zähler',
                resizable: false,
                sortable: true,
                width: 50
            },{
                dataIndex: 'prioritaet',
                id: 'brick',
                editable: false,
                groupable: false,
                hidden: false,
                header: 'Priorität',
                resizable: false,
                sortable: true,
                renderer: rendererPrioritaet,
                width: 50
            },{
                dataIndex: 'de',
                editable: false,
                groupable: false,
                hidden: false,
                header: 'Name de',
                resizable: false,
                sortable: true,
                width: 200
            },{
                dataIndex: 'en',
                editable: false,
                groupable: false,
                hidden: false,
                header: 'Name en',
                resizable: false,
                sortable: true,
                width: 200
        }],
        buttons: [{
            text: 'Programmkategorien speichern',
            handler: function(){
                programmKategorienSpeichern();
            }

        }]
    });


    return{
        start: function()
        {
            return tabelleKategorien;
        },
        kategorienErmitteln: function(idProgramme)
        {
            programmId = idProgramme;

            checkedRows(idProgramme);
        },
        setProgrammId: function(ProgrammId){
            programmId = ProgrammId;
        }
    }
}
