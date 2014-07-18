Ext.onReady(function(){

    jsonStoreGridPreisVarianten = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: '/admin/datensatz/getpreisvarianten',
        id: 'storeGridPreisvarianten',
        fields: ['id', 'bezeichnung', 'anzahl', 'ansatz', 'preistyp', 'zuschlag']
    });

    gridPreisVarianten = new Ext.grid.GridPanel({
        id: 'gridPreisVarianten',
        height: 380,
        width: 550,
        stripeRows: true,
        columnLines: true,
        autoExpandColumn: 'bezeichnung',
        store: jsonStoreGridPreisVarianten,
        title: 'Preisvarianten',
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
            // editor: 'textField'
            renderer: rendererPreis
        },{
            xtype: 'gridcolumn',
            dataIndex: 'preistyp',
            header: 'Preistyp',
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
            text: 'speichern',
            handler: speichereZuschlaege
        },{
            text: 'alle Zuschläge löschen',
            handler: loeschenZuschlaege
        }]
    });

    gridPreisVarianten.on('keydown',function(e,el) {
		if (e.getCharCode() == e.BACKSPACE) {
			e.stopEvent();
		}
	});
    
});

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
            showMsgBox('Zuschläge wurden eingetragen');
            jsonStoreGridPreisVarianten.reload();
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
        return '>';
    else if(val == '3')
        return '=';
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