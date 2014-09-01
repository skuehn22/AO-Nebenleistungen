/**
 * Verwaltet die Stammdaten eines Hotels
 *
 *
 */

function fillStammdaten(){

    if(!grid.getSelectionModel().hasSelection()){
        showMsgBox('Hotel auswählen');

        return;
    }

    if(!memoryHotelId){
        showMsgBox("Hotel auswählen");

        return;
    }

    var formStammdaten = new Ext.form.FormPanel({
        width: 550,
        autoHeight: true,
        frame: true,
        padding: 10,
        id: 'stammdatenForm',
        border: false,
        url: '/admin/hotels/updatehotels/',
        method: 'post',
        labelWidth: 150,
        items: [{
                xtype: 'textfield',
                fieldLabel: 'Hotelname *',
                width: 200,
                id: 'property_name',
                allowBlank: false,
                helpText: 'Bitte Hotelname eingeben'
            },{
                xtype: 'textfield',
                fieldLabel: 'Hotelcode *',
                width: 200,
                id: 'property_code',
                allowBlank: false,
                helpText: 'Bitte Hotelcode eingeben'
            },{
                xtype: 'numberfield',
                fieldLabel: 'Gewinnspanne',
                helpText: 'Gewinnspanne in Prozent',
                allowBlank: false,
                name: 'gewinnspanne',
                allowDecimals: false,
                allowNegative: false
            },{
                xtype: 'combo',
                fieldLabel: 'aktiv *',
                allowBlank: false,
                forceSelection: true,
                triggerAction: 'all',
                valueField: 'id',
                hiddenName: 'aktiv',
                width: 100,
                mode: 'local',
                store: [['2', 'passiv'],['3', 'aktiv']],
                displayField: 'aktiv',
                helpText: 'Bitte Hotel aktiv / passiv schaltent'
            },{
                xtype: 'textfield',
                fieldLabel: 'ID',
                width: 50,
                id: 'id',
                hidden: true
        },{
                xtype: 'displayfield',
                fieldLabel: 'Hinweis',
                readonly: true,
                value: 'Mindestpersonenanzahl 10 Personen'
        },{
                xtype: 'numberfield',
                id: 'numberPeopleTravelGroup',
                fieldLabel: 'Personenanzahl einer Reisegruppe *',
                allowDecimals: false,
                maxValue: 50,
                width: 50,
                allowBlank: false,
                helpText: 'Min. Anzahl der Personen die als Gruppe gerechnet werden'
        },{
                xtype: 'checkbox',
                id: 'overbook',
                fieldLabel: 'Überbuchung möglich',
                helpText: 'es sind Überbuchungen möglich',
                checked: false
        }]
    });

    var stammdatenFenster = new Ext.Window({
        title: 'Stammdaten Hotel, Hotel ID: ' + memoryHotelId,
        width: 585,
        autoHeight: true,
        padding: 10,
        x: 20,
        modal: true,
        shadow: false,
        resizable: false,
        closable: showCloseButton,
        y: 20,
        height: 400,
        buttons: [{
            text: 'eintragen',
            id: 'aendern',
            icon: '/buttons/vor.png',
            cls: 'x-btn-text-icon',
            iconAlign: 'right',
            handler: function(){
                formStammdaten.getForm().submit({
                    success: function(form,action){
                        jsonStore.load(); // neuladen Tabelle 'parent view'
                        stammdatenFenster.close();
                        fillPersonaldaten();
                    },
                    failure: function(form,action){
                        showMsgBox('Fehler');
                    }
                });
            }
        }]
    });

    formStammdaten.load({
        url: '/admin/hotels/loadform/',
        method: 'post',
        params: {
            id: memoryHotelId
        }
    });

    stammdatenFenster.add(formStammdaten);
    stammdatenFenster.doLayout();
    stammdatenFenster.show();
}