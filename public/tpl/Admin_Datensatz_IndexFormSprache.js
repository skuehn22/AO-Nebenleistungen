function fillSprache(){
    if(!programmId){
        showMsgBox('Bitte Programm auswählen');

        return;
    }

    spracheStore = new Ext.data.JsonStore({
        root: 'data',
        method: 'post',
        url: '/admin/datensatz/getlanguages/',
        id: 'spracheStore',
        fields: ['id', 'de', 'flag','check'],
        baseParams: {
            programmId: ''
        },
        listeners: {
            load: function(store, records){
                var records = [];
                var programmWurdeGewaehlt = 2;
                store.each(function(record){
                    if(record.get('check') == programmWurdeGewaehlt){
                        records.push(record);
                    }
                });
                sprache.getSelectionModel().selectRecords(records);
            }
        }
    });

    var spracheCheckboxSelectModel = new Ext.grid.CheckboxSelectionModel(),

    sprache = new Ext.grid.GridPanel({
        width: 400,
        autoHeight: true,
        autoExpandColumn: 'sprache',
        store: spracheStore,
        stripeRows: true,
        loadMask: true,
        sm: spracheCheckboxSelectModel,
        columnLines: true,
        columns: [{
                    xtype: 'gridcolumn',
                    header: 'ID',
                    sortable: true,
                    dataIndex: 'id',
                    width: 50,
                    id: 'id',
                    hidden: true
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'de',
                    header: 'Sprache',
                    sortable: true,
                    dataIndex: 'de',
                    width: 100,
                    id: 'sprache'
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'flag',
                    header: 'Symbol',
                    sortable: true,
                    dataIndex: 'flag',
                    width: 100,
                    renderer: rendererFlag
                },
                spracheCheckboxSelectModel
        ]
    });

    var spracheFenster = new Ext.Window({
        title: 'Programmsprachen, ID: ' + programmId + ', Programmname: ' + programmName,
        width: 430,
        padding: 10,
        border: false,
        x: 20,
        y: 20,
        modal: true,
        shadow: false,
        closable: true,
        resizable: false
    });

    var buttonEintragen = {
        text: 'eintragen',
        icon: '/buttons/vor.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'right',
        listeners: {
            click: function(){

                var languages = new Array();
                languages = sprache.getSelectionModel().getSelections();

                var selectedLanguages = new Array();
                for(var i=0; i<languages.length; i++){
                    selectedLanguages[i] = languages[i].data.id;
                }

                var languagesFromProgram = Ext.util.JSON.encode(selectedLanguages);

                Ext.Ajax.request({
                   url: '/admin/datensatz/switchlanguage/',
                   success: function(request, action){
                        spracheFenster.close();
                        fillOeffnungszeiten();
                   },
                   params: {
                       languages: languagesFromProgram,
                       programId: programmId
                   }
                });
            }
        }
    };

    var buttonTemplateEnglisch = {
        xtype: 'button',
        text: 'englische Beschreibung',
        icon: '/buttons/zurueck.png',
        cls: 'x-btn-text-icon',
        iconAlign: 'left',
        handler: function(){
            spracheFenster.close();
            fillTemplate(2);
        }
    };

    spracheFenster.add(sprache);
    spracheFenster.addButton(buttonTemplateEnglisch);
    spracheFenster.addButton(buttonEintragen);
    spracheFenster.doLayout();
    spracheFenster.show();

    spracheStore.setBaseParam('programmId', programmId);
    spracheStore.load();

    return;
}
