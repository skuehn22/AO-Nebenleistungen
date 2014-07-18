function fillPreise(){

     if(!programmId){
        showMsgBox('Bitte Programm auswählen');
        return;
    }
    
    var storePreiseMwst = new Ext.data.SimpleStore({
        fields: ['mwstId','mwst'],
        data: [['A','19%'],['B','7%'],['C','0%']]
    });

    jsonStoreGridPreisVarianten = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: '/admin/datensatz/getpreisvarianten',
        id: 'storeGridPreisvarianten',
        baseParams: {
            programmId: programmId
        },
        fields: ['id', 'bezeichnung', 'anzahl', 'ansatz', 'preistyp', 'zuschlag', 'bezug']
    });

    gridPreisVarianten = new Ext.grid.GridPanel({
        id: 'gridPreisVarianten',
        height: 500,
        width: 650,
        stripeRows: true,
        columnLines: true,
        autoExpandColumn: 'bezeichnung',
        store: jsonStoreGridPreisVarianten,
        title: 'Preisvarianten, alle Preisvarianten sind Nettopreise',
        viewConfig: {
            forceFit: true,
            scrollOffset: 0
        },
        style: {
            marginLeft: '10px'
        },
        columns: [{
            xtype: 'gridcolumn',
            dataIndex: 'id',
            header: 'Id',
            id: 'id',
            sortable: false,
            width: 100,
            hidden: true
        },{
            xtype: 'gridcolumn',
            dataIndex: 'bezeichnung',
            header: 'Bezeichnung',
            id: 'bezeichnung',
            sortable: false,
            width: 150
        },{
            xtype: 'gridcolumn',
            dataIndex: 'bezug',
            header: 'Bezugsgröße',
            id: 'bezug',
            sortable: false,
            width: 100
        },{
            xtype: 'gridcolumn',
            dataIndex: 'ansatz',
            header: 'Ansatz',
            id: 'ansatz',
            sortable: true,
            width: 50,
            renderer: rendererAnsatz
        },{
            xtype: 'gridcolumn',
            dataIndex: 'anzahl',
            header: 'Anzahl',
            id: 'anzahl',
            sortable: true,
            width: 50
        },{
            xtype: 'gridcolumn',
            dataIndex: 'zuschlag',
            header: 'Zuschlag',
            id: 'zuschlag',
            sortable: true,
            width: 100,
            renderer: rendererPreis
        },{
            xtype: 'gridcolumn',
            dataIndex: 'preistyp',
            header: 'Verrechnung',
            id: 'preistyp',
            sortable: true,
            width: 100,
            renderer: rendererPreisTyp
        }],
        bbar: {
            xtype: 'paging',
            displayInfo: true,
            store: jsonStoreGridPreisVarianten,
            pageSize: 10
        },
        buttons: [{
            text: 'alle Zuschläge löschen',
            handler: loeschenZuschlaege
        }]
    });

//    gridPreisVarianten.on('keydown',function(e,el) {
//		if (e.getCharCode() == e.BACKSPACE) {
//			e.stopEvent();
//		}
//	});

    preiseForm = new Ext.form.FormPanel({
		width: 350,
        frame: true,
        autoHeight: true,
        border: false,
        padding: 10,
        labelWidth: 120,
        url: '/admin/datensatz/setprices/',
        method: 'post',
        id: 'preiseForm',
        autoHeight: true,
        items: [{
                xtype: 'numberfield',
                fieldLabel: 'Netto Einkaufspreis Euro *',
                id: 'ek',
                width: 220,
                decimalPrecision: 2,
                width: 100,
                allowBlank: false
            },{
                xtype: 'numberfield',
                fieldLabel: 'Netto Verkaufspreis Euro *',
                id: 'vk',
                width: 220,
                decimalPrecision: 2,
                width: 100,
                allowBlank: false
            },{
                xtype: 'combo',
                fieldLabel: 'Mehrwertsteuer',
                width: 100,
                allowBlank: false,
                forceSelection: true,
                triggerAction: 'all',
                displayField: 'mwst',
                valueField: 'mwstId',
                hiddenName: 'mwst_satz',
                helpText: 'Mehrwertsteuer länderspezifisch',
                store: storePreiseMwst,
                mode: 'local',
                lazyRender:true,
                typeAhead: true
            },{
                xtype: 'radiogroup',
                fieldLabel: 'Preistyp',
                items: [
                    {boxLabel: 'Personenpreis', name: 'gruppenpreis', inputValue: 1},
                    {boxLabel: 'Gruppenpreis', name: 'gruppenpreis', inputValue: 2}
                ]

        }]
    });

    var fensterPreise = new Ext.Window({
        layout: 'hbox',
        title: 'Preise ID: ' + programmId,
        shadow: false,
        width: 1050,
        autoHeight: true,
        border: false,
        closable: showCloseButton,
        modal: true,
        x: 20,
        y: 20,
        padding: 10
    });

    var eintragenButton ={
        icon: '/buttons/vor.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'right',
        text: 'eintragen',
            handler: function(){
                Ext.getCmp('preiseForm').getForm().submit({
                    params: {
                        programmId: programmId
                    },
                    success: function(form,action){
                        speichereZuschlaege();
                        fensterPreise.close();
                        fillDiverses();
                    },
                    failure: function(form,action){
                        showMsgBox('Bitte alle Pflichtfelder ausfüllen');
                    }
                });
            }
    };

    var termineButton = {
        xtype: 'button',
        text: 'Termine',
        icon: '/buttons/zurueck.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'left',
        handler: function(){
            fensterPreise.close();
            fillTermine();
        }
    };

    jsonStoreGridPreisVarianten.load();

    fensterPreise.add(preiseForm);
    fensterPreise.add(gridPreisVarianten);
    fensterPreise.addButton(termineButton);
    fensterPreise.addButton(eintragenButton);
    fensterPreise.doLayout();
    fensterPreise.show();

    preiseForm.load({
        url: '/admin/datensatz/findprices/',
        params: {
            programmId: programmId
        }
    });
}





function speichereZuschlaege(){
    var zuschlaege = '';

    var selected = gridPreisVarianten.store.each(function(record){
        var value = Ext.get('zuschlag' + record.data.id).getValue();
        value = value.replace(',','.');
        value = parseFloat(value);

        if(value == 0 || isNaN(value))
            return;

        var id = record.data.id;
        zuschlaege += id + ":" + value + "#";
    });

    if(zuschlaege.length == 0)
        return;

    Ext.Ajax.request({
        url: '/admin/datensatz/setzuschlaege',
        method: 'post',
        params: {
            programmId: programmId,
            zuschlaege: zuschlaege
        },
        success: function(){
            // jsonStoreGridPreisVarianten.reload();

            return;
        },
        failure: function(){
            showMsgBox('eintragen fehlgeschlagen');
        }
    });


    return;
}

function loeschenZuschlaege(){
     Ext.Ajax.request({
        url: '/admin/datensatz/deletezuschlaege',
        method: 'post',
        params: {
            programmId: programmId
        },
        success: function(){
            showMsgBox('alle Zuschläge wurden gelöscht');
            jsonStoreGridPreisVarianten.reload();
        },
        failure: function(){
            showMsgBox('löschen fehlgeschlagen');
        }
    });

    return;
}


function rendererAnsatz(val){
    if(val == '1')
        return '<';
    else if(val == '2')
        return '=';
    else if(val == '3')
        return '>';
}

function rendererPreisTyp(val){
    if(val == '1')
        return 'Festpreis';
    else if(val == '2')
        return 'Zuschlag';
    else if(val == '3')
        return 'Faktor';
}

function rendererPreis(val){
    var append = '';
    var prepend = '';

    if(val == '' || val == 'undefined')
        val = 0;

    if(arguments[2].data.preistyp == '1'){
        prepend = ' €';
    }
    else if(arguments[2].data.preistyp == '2'){
        append = '+ ';
        prepend = ' €';
    }
    else if(arguments[2].data.preistyp == '3'){
        prepend = ' %';
        append = '* ';
    }

    var preis = append + "<input type='text' size='5' value='" + val +"' id='zuschlag" + arguments[2].data.id + "'>" + prepend;

    return preis;
}