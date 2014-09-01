var AdminZugangskontrolleIndex = function () {
    // Begin private

    var suchParameterBereich = null;
    var suchParameterController = null;

    function ladeFormular(){
        var isSelected = tabelle.getSelectionModel().hasSelection();

        if(!isSelected){
            showMsgBox('Bitte Action auswählen');
            return;
        }

        formularZugriffsrechte.getForm().reset();

        var dataSelectedGrid = tabelle.getSelectionModel().getSelected();
        var formularFelder = formularZugriffsrechte.getForm().items.each(function(){
            if(arguments[0].name == 'id')
                arguments[0].setValue(dataSelectedGrid.data.id);
            else if(arguments[0].name == 'module')
                arguments[0].setValue(dataSelectedGrid.data.module);
            else if(arguments[0].name == 'controller')
                arguments[0].setValue(dataSelectedGrid.data.controller);
            else if(arguments[0].name == 'action')
                arguments[0].setValue(dataSelectedGrid.data.action);
        });

        memoryIdAction = dataSelectedGrid.data.id;

        formularZugriffsrechte.getForm().load({
            url: '/admin/zugangskontrolle/ermittle-zugriffsrechte/',
            method: 'post',
            params: {
                id: dataSelectedGrid.data.id
            }
        });

    }

    function sucheDatensaetze(){
        suchParameterBereich = Ext.getCmp('searchBereich').getValue();
        suchParameterController = Ext.getCmp('searchController').getValue();

        jsonstoreTabelle.setBaseParam('sucheBereich', suchParameterBereich);
        jsonstoreTabelle.setBaseParam('sucheController', suchParameterController);

        jsonstoreTabelle.load();
    }

    function speichernZugriffsrechte(){

        if(memoryIdAction == null){
            showMsgBox('Bitte Action wählen');

            return;
        }

        formularZugriffsrechte.getForm().submit({
           params: {
               idAction: memoryIdAction
           },
           success: function(){
               showMsgBox('Zugriffsrechte gespeichert');
           }
        });

        return;
    }

    var selModel = new Ext.grid.RowSelectionModel();

    var jsonstoreTabelle = new Ext.data.JsonStore({
        url: '/admin/zugangskontrolle/show/',
        root: 'data',
        totalProperty: 'anzahl',
        baseParams: {
            sucheBereich: suchParameterBereich,
            sucheController: suchParameterController
        },
        fields: [{
            name: 'id'
        },{
            name: 'module'
        },{
            name: 'controller'
        },{
            name: 'action'
        }]
    });

    var tabelle = new Ext.grid.GridPanel({
        id: 'tabelle',
        width: 589,
        autoHeight: true,
        title: 'Zugangskontrolle auf Action eines Controller',
        columnLines: true,
        loadMask: true,
        stripeRows: true,
        sm: selModel,
        store: jsonstoreTabelle,
        renderTo: 'tabelleZugangskontrolle',
        listeners: {
            rowdblclick:  function(){
                ladeFormular();
            }
        },
        viewConfig: {
            scrollOffset: 0,
            forceFit: true
        },
        columns: [{
            xtype: 'gridcolumn',
            dataIndex: 'id',
            editable: false,
            groupable: false,
            header: 'ID',
            hideable: false,
            id: 'id',
            resizable: false,
            sortable: true,
            width: 75
        },{
            xtype: 'gridcolumn',
            dataIndex: 'module',
            editable: false,
            groupable: false,
            header: 'Bereich',
            hideable: false,
            resizable: false,
            sortable: true,
            width: 100
        },{
            xtype: 'gridcolumn',
            dataIndex: 'controller',
            header: 'Controller',
            id: 'controller',
            sortable: true,
            width: 100
        },{
            xtype: 'gridcolumn',
            dataIndex: 'action',
            header: 'Action',
            sortable: true,
            width: 100
        }],
        bbar: [{
            xtype: 'paging',
            store: jsonstoreTabelle,
            id: 'paging',
            pageSize: 20,
            displayMsg: "Anzeige: {0} - {1} von {2} ",
            displayInfo: true
        }],
        tbar: [{
            xtype: 'tbspacer',
            width: 20
        },{
            xtype: 'tbseparator'
        },{
            text: 'Bereich: ',
        },{
            xtype: 'textfield',
            width: 100,
            id: 'searchBereich'
        },{
            xtype: 'tbspacer',
            width: 10
        },{
            text: 'Controller: '
        },{
            xtype: 'textfield',
            width: 100,
            id: 'searchController'
        },{
            xtype: 'tbseparator'
        },{
            xtype: 'tbbutton',
            text: 'suchen',
            handler: function(){
                sucheDatensaetze();
            }
        },{
            xtype: 'tbseparator'
        }],
        fbar: [{
            xtype: 'tbbutton',
            text: 'Zugriffsrecht bearbeiten',
            handler: function(){
                ladeFormular();
            }
        }]
    });

    var formularZugriffsrechte = new Ext.form.FormPanel({
        id: 'zugriffsrechte',
        autoHeight: true,
        autoWidth: true,
        padding: 10,
        labelWidth: 150,
        title: 'Zugriffsrechte',
        url: '/admin/zugangskontrolle/update-zugriffsrechte/',
        method: 'post',
        params: {
            idAction: memoryIdAction
        },
        items: [{
            xtype: 'displayfield',
            name: 'id',
            width: 150,
            fieldLabel: 'ID'
        },{
            xtype: 'displayfield',
            width: 150,
            name: 'module',
            fieldLabel: 'Bereich'
        },{
            xtype: 'displayfield',
            width: 150,
            name: 'controller',
            fieldLabel: 'Controller'
        },{
            xtype: 'displayfield',
            name: 'action',
            width: 150,
            fieldLabel: 'Action'
        }],
        fbar: [{
            xtype: 'tbbutton',
            text: 'Zugriffsrecht speichern',
            handler: function(){
                speichernZugriffsrechte();
            }
        }]
    });

    function neueCheckboxenAnfuegen(){

        Ext.each(memoryCheckboxenRollen, function(checkbox, key){

            var id = checkbox[0];
            var label = checkbox[1];

            checkboxRolle.fieldLabel = label;
            checkboxRolle.name = 'checkbox' + id;

            formularZugriffsrechte.add(checkboxRolle);
        });

        return;
    }

    var checkboxRolle = {
        xtype: 'checkbox',
        name: '',
        fieldLabel: ''
    };

    function alleActionEintragen(){
        Ext.Ajax.request({
            url: '/admin/zugangskontrolle/action-eintragen',
            method: 'post',
            success: function(){
                jsonstoreTabelle.load();
                showMsgBox("Action's eingetragen");
            }
        });
    }

    function buildActionPanel(){
        var buttonPanel = new Ext.Panel({
            title: 'alle Action ändern / eintragen',
            width: 330,
            autoHeight: 100,
            renderTo: 'button',
            padding: '10 10 10 10',
            defaults: {
                padding: '10 10 10 10'
            },
            items: [{
                html: 'Durchsucht alle vorhandenen Controller.<br>Bei Bedarf werden die Action der Controller registriert'
            }],
            fbar: [{
                xtype: 'tbbutton',
                text: "alle 'Action' eintragen",
                handler: function(){
                    alleActionEintragen();
                }
            }]
        });

        return;
    }


    // End private
    // Begin public
    return{
        start: function(){

            if(memoryShowBlock)
                buildActionPanel();

            neueCheckboxenAnfuegen();
            formularZugriffsrechte.render('formZugangskontrolle');
            jsonstoreTabelle.load();
        }

    }

    // End public
}

Ext.onReady(function(){
    var zugangskontrolle = new AdminZugangskontrolleIndex();
    zugangskontrolle.start();
});