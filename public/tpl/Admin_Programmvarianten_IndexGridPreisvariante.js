/**
 * Created by JetBrains PhpStorm.
 * User: Stephan.Krauss
 * Date: 12.12.11
 * Time: 12:40
 * To change this template use File | Settings | File Templates.
 */

Ext.onReady(function(){

    gridPreisvariantenJsonStore = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/programmvarianten/gridprogrammvarianten/",
        id: 'storeGridprogrammvarianten',
        fields: ['id','bezeichnung','ansatz','anzahl','preistyp','bezug','variantengruppe']
    });

    gridPreisvariantenJsonStore.load();

    gridPreisvarianten = new Ext.grid.GridPanel({
        id: 'gridPreisvariante',
        autoHeight: true,
        store: gridPreisvariantenJsonStore,
        width: 600,
        title: 'vorhandene Preisvarianten der Programme',
        autoExpandColumn: 'bezeichnung',
        renderTo: 'divGridPreisvarianten',
        columnLines: true,
        stripeRows: true,
        listeners: {
            rowdblclick: aufrufVorhandenePreisvariante
        },
        viewConfig: {
            forceFit: true,
            scrollOffset: 0
        },
         columns: [{
                        xtype: 'gridcolumn',
                        dataIndex: 'id',
                        header: 'ID',
                        id: 'id',
                        width: 50,
                        hidden: true
                    },{
                        xtype: 'gridcolumn',
                        dataIndex: 'variantengruppe',
                        header: 'Preisgruppe',
                        id: 'variantengruppe',
                        width: 50,
                        renderer: rendererPreisgruppe
                    },{
                        xtype: 'gridcolumn',
                        dataIndex: 'bezeichnung',
                        header: 'Bezeichnung',
                        id: 'bezeichnung',
                        width: 100
                    },{
                        xtype: 'gridcolumn',
                        dataIndex: 'bezug',
                        header: 'Bezugsgröße',
                        id: 'bezug',
                        width: 100
                    },{
                        xtype: 'gridcolumn',
                        dataIndex: 'ansatz',
                        header: 'Ansatz',
                        id: 'ansatz',
                        width: 50,
                        renderer: rendererAnsatz
                    },{
                        xtype: 'gridcolumn',
                        dataIndex: 'anzahl',
                        header: 'Anzahl',
                        id: 'anzahl',
                        width: 50
                    },{
                        xtype: 'gridcolumn',
                        dataIndex: 'preistyp',
                        header: 'Preistyp',
                        id: 'preistyp',
                        width: 100,
                        renderer: rendererPreisTyp
                    }],
                bbar: {
                    xtype: 'paging',
                    displayInfo: true,
                    store: gridPreisvariantenJsonStore,
                    pageSize: 10
                },
                buttons: [{
                    text: 'Preisvariante bearbeiten',
                    listeners: {
                        click: aufrufVorhandenePreisvariante
                    }
                },{
                    text: 'neue Preisvariante',
                    handler: function(){
                        neueProgrammvariante();
                    }
                },{
                    text: 'löschen Preisvariante',
                    handler: function(){
                        loeschenPreisVariante();
                    }
                }]
    });

});

function rendererPreisgruppe(val){
    var inhalt = "<span style='color: blue;'>" + val + "</span>";
    
    return inhalt;
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

function neueProgrammvariante(){
    Ext.getCmp('preisvarianteSpeichern').setText('neue Preisvariante speichern');
    formPreisvarianten.getForm().reset();
    Ext.getCmp('formPreisvariante').setTitle('neue Preisvariante');

    return;
}

function aufrufVorhandenePreisvariante(){
    var model = gridPreisvarianten.getSelectionModel();

    if(!model.hasSelection()){
      showMsgBox('Bitte Preisvariante auswählen');

      return;
    }

    Ext.getCmp('formPreisvariante').setTitle('vorhandene Preisvariante überarbeiten');
    Ext.getCmp('preisvarianteSpeichern').setText('vorhandene Preisvariante speichern');

    var id = model.getSelected().data.id;

    formPreisvarianten.load({
        params: {
            loadId: id
        }
    });

}
