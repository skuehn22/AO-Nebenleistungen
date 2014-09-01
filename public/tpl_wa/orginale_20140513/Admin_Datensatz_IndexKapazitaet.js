/**
 * Verwaltung der Kapazität eies Programmes
 *
 *
 */

var AdminDatensatzIndexKapazitaetClass = function(){

    // Beginn Private
    var AnzeigeFenster = null;
    var defaultEingabe = null;
    var kapazitaetEingabe = null;
    var kapazitaetProgramm = null;
    var jsonStoreTabelleKapazitaet = null;
    var checkBoxSel = null;


    jsonStoreTabelleKapazitaet = new Ext.data.JsonStore({
        totalProperty: 'anzahl',
        root: 'data',
        method: 'post',
        url: "/admin/datensatz-standard-kapazitaet/show/",
        id: 'jsonStoreTabelleKapazitaet',
        fields: ['id','datum','zeit','wochentag','kapazitaet'],
        baseParams: {
            programmId: programmId
        }
    });

    /**
     * Löscht die
     * ausgewählten Kapazitäten
     *
     */
    function kapazitaetLoeschen(){
        var zuLoeschendeDatensaetze = '';
        var selected = kapazitaetProgramm.getSelectionModel().getSelections();

        for(var i=0; i < selected.length; i++){
            zuLoeschendeDatensaetze += selected[i].data.id + ",";
        }

        if(selected.length > 0){
            Ext.Ajax.request({
               url: '/admin/datensatz-standard-kapazitaet/delete/',
               method: 'post',
               params: {
                   loeschen: zuLoeschendeDatensaetze
               },
               success: function(response, ajax){
                   showMsgBox('Kapazitäten gelöscht');
                   jsonStoreTabelleKapazitaet.load();
               }
           });
        }
    }

    /**
     * Erstellt das Anzeigefenster der View
     *
     *
     */
    function Fenster(){

        AnzeigeFenster = new Ext.Window({
            title: 'Kapazität eines Programmes, ID: ' + programmId + ", Programmname: " + programmName,
            shadow: false,
            width: 600,
            autoHeight: true,
            layout: 'table',
            verticalAlign: 'middle',
            closable: showCloseButton,
            draggable: false,
            resizable: false,
            layoutConfig: {
                columns: 1
            },
            border: false,
            modal: true,
            padding: 20,
            x: 20,
            y: 20
        });
    }

    /**
     * Sendet den Inhalt des Formular
     * 'defaultEingabe' an den Server.
     * Standard Kapazität eines Programmes
     * wird gespeichert.
     */
    function speichernStandardKapazitaet(){
        var form = defaultEingabe.getForm().submit({
            params: {
                programmId: programmId
            },
            success: function(form, action){

                var messageRaw = action.response.responseText;
                var message = Ext.util.JSON.decode(messageRaw);

                if(message.success == true){
                    showMsgBox('Standard Kapazität des Programmes wurde gespeichert');

                    var defaultKapazitaet = defaultEingabe.getForm().findField('kapazitaet').getValue();
                    defaultKapazitaet = parseInt(defaultKapazitaet, 10);
                    defaultEingabe.getForm().findField('kapazitaet').setValue(defaultKapazitaet);
                    kapazitaetEingabe.getForm().findField('kapazitaet').setValue(defaultKapazitaet);
                }
                else
                    showMsgBox('Kapazität des Programmes wurde nicht gespeichert');

            }
        });
    }

    /**
     * Baut Formular für
     * Standard Eingabe eines Programmes
     */
    function defaultFormular(){

        defaultEingabe = new Ext.form.FormPanel({
            method: 'post',
            url: '/admin/datensatz-standard-kapazitaet/set-default/',
            id: 'defaultKapazitaet',
            height: 110,
            width: 450,
            padding: 10,
            labelWidth: 200,
            title: 'Default Kapazität des Programmes',
            items: [{
                xtype: 'textfield',
                width: 50,
                maskRe: /[0-9]/i,
                name: 'kapazitaet',
                allowBlank: false,
                value: 100,
                fieldLabel: 'Standardkapazität *',
                helpText: 'Standard Kapazität eines Programmes'
            }],
            fbar: {
                xtype: 'toolbar',
                items: [{
                    xtype: 'button',
                    text: 'Standard Kapazität speichern',
                    handler: function(){
                        speichernStandardKapazitaet();
                    }
                }]
            }
        });
    }

    /**
     * Baut Formular für Eingabe
     * Kapazität eines Programmes
     */
    function kapazitaetFormular(){

        kapazitaetEingabe = new Ext.form.FormPanel({
            height: 160,
            width: 400,
            padding: 10,
            url: '/admin/datensatz-standard-kapazitaet/create/',
            method: 'post',
            title: 'Tageskapazität des Programmes',
            items: [{
                    xtype: 'textfield',
                    name: 'kapazitaet',
                    width: 50,
                    value: 100,
                    allowBlank: false,
                    maskRe: /[0-9]/i,
                    fieldLabel: 'Kapazität *',
                    helpText: 'Standard Kapazität eines Programmes an einem bestimmten Tag'
                },{
                    xtype: 'datefield',
                    name: 'datum',
                    width: 150,
                    fieldLabel: 'Datum',
                    allowBlank: false,
                    helpText: "Datum der Tageskapazität"
                },{
                    xtype: 'textfield',
                    name: 'zeit',
                    width: 150,
                    value: '00:00',
                    readOnly: true,
                    fieldLabel: 'Zeit',
                    helpText: "Wird als Zeit '00:00' angegeben, dann gilt die Kapazität für den gesamten Tag"
                }],
                fbar: {
                    xtype: 'toolbar',
                    items: [{
                        xtype: 'button',
                        text: 'Kapazität für den Tag eintragen',
                        handler: function(){
                            kapazitaetEingabe.getForm().submit({
                                params: {
                                    programmId: programmId
                                },
                                success: function(form, action){
                                    var messageRaw = action.response.responseText;
                                    var message = Ext.util.JSON.decode(messageRaw);

                                    if(message.success == 'true')
                                        showMsgBox('Kapazität wurde eingetragen');
                                    else
                                        showMsgBox('Kapazität wurde eingetragen');

                                    jsonStoreTabelleKapazitaet.load();
                                }
                            });
                        }
                    }]
                }
        });
    }

    /**
     * Setzt die Standardkapazität
     * eines Programmes in den Formularen
     * während des Erststart der View
     *
     */
    function setDefaultKapazitaet(){
        Ext.Ajax.request({
            url: '/admin/datensatz-standard-kapazitaet/get-default/',
            method: 'post',
            params: {
                programmId: programmId
            },
            success: function(response, ajax){

                var standardKapazitaetRaw = response.responseText;
                var standardKapazitaet = Ext.util.JSON.decode(standardKapazitaetRaw);

                defaultEingabe.getForm().findField('kapazitaet').setValue(standardKapazitaet.data.kapazitaet);
                kapazitaetEingabe.getForm().findField('kapazitaet').setValue(standardKapazitaet.data.kapazitaet);
            }
        });
    }

    function tabelleKapazitaet(){

        checkBoxSel = new Ext.grid.CheckboxSelectionModel();

        kapazitaetProgramm = new Ext.grid.GridPanel({
            width: 550,
            autoHeight: true,
            stripeRows: true,
            loadMask: true,
            columnLines: true,
            autoExpandColumn: 'datum',
            store: jsonStoreTabelleKapazitaet,
            sm: checkBoxSel,
            title: 'Kapazität des Programmes',
            viewConfig: {
                forceFit: true,
                scrollOffset: 0
            },
            columns: [
                checkBoxSel,
            {
                xtype: 'gridcolumn',
                dataIndex: 'string',
                header: 'Datum',
                name: 'datum',
                sortable: true,
                dataIndex: 'datum',
                width: 100
            },{
                xtype: 'gridcolumn',
                header: 'Wochentag',
                sortable: true,
                dataIndex: 'wochentag',
                width: 100
            },{
                xtype: 'gridcolumn',
                header: 'Zeit',
                sortable: true,
                dataIndex: 'zeit',
                width: 100
            },{
                xtype: 'gridcolumn',
                header: 'Kapazität',
                dataIndex: 'kapazitaet',
                sortable: true,
                width: 100
            }],
            bbar: {
                xtype: 'paging',
                store: jsonStoreTabelleKapazitaet,
                id: 'pagingKapazitaet',
                pageSize: 10,
                displayMsg: "Anzeige: {0} - {1} von {2} ",
                displayInfo: true
            },
            fbar: [{
                xtype: 'button',
                text: 'Kapazität löschen',
                handler: function(){

                    Ext.Msg.confirm('löschen','markierte Kapazitäten wirklich löschen ?',function(){
                        kapazitaetLoeschen();
                    });
                }
            }]
        });
    }

    // Ende Private

    // Beginn Public
    return{
        init: function(){
            if(programmId == null){
                showMsgBox('Bitte Programm auswählen');

                return;
            }

            if(programmName == null){
                showMsgBox('Programmname unbekannt');

                return;
            }

            Fenster();
            defaultFormular();
            kapazitaetFormular();
            tabelleKapazitaet();
            AnzeigeFenster.add(kapazitaetEingabe);
            AnzeigeFenster.add(kapazitaetProgramm);
            AnzeigeFenster.add(defaultEingabe);
            AnzeigeFenster.show();

            setDefaultKapazitaet();
            jsonStoreTabelleKapazitaet.load();



        }
    }

    // Ende Public
}

function fillKapazitaet(){

    var object = new AdminDatensatzIndexKapazitaetClass();
    object.init();


}
