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

    var storeHotels = new Ext.data.JsonStore({
        url: '/admin/Ratenverfuegbarkeit/hotellist/',
        root: 'data',
        fields: ['id','hotelname']
    });

    var storeRaten = new Ext.data.JsonStore({
        url: '/admin/Ratenverfuegbarkeit/getratenhotel/',
        root: 'data',
        fields: ['id', 'ratenname']
    });

    var allgemeineAngaben = [{
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
        forceSelection: true,
        triggerAction: 'all',
        name: 'aktiv',
        hiddenName: 'aktiv',
        width: 100,
        mode: 'local',
        store: [['2', 'passiv'],['3', 'aktiv']],
        helpText: 'Bitte Hotel aktiv / passiv schaltent',
        typeAhead: false
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
    }];

    var tagesAngaben = [{
            xtype: 'checkboxgroup',
            id: 'anreise',
            width: 400,
            helpText: 'mögliche Anreisetage in der Woche',
            fieldLabel: 'Anreisetage',
            items: [{
                xtype: 'checkbox',
                id: 'montagAn',
                value: 1,
                boxLabel: 'Mo.'
            },{
                xtype: 'checkbox',
                id: 'dienstagAn',
                value: 1,
                boxLabel: 'Die.'
            },{
                xtype: 'checkbox',
                id: 'mittwochAn',
                value: 1,
                boxLabel: 'Mi.'
            },{
                xtype: 'checkbox',
                id: 'donnerstagAn',
                value: 1,
                boxLabel: 'Do.'
            },{
                xtype: 'checkbox',
                id: 'freitagAn',
                value: 1,
                boxLabel: 'Fr.'
            },{
                xtype: 'checkbox',
                id: 'sonnabendAn',
                value: 1,
                boxLabel: 'Sa.'
            },{
                xtype: 'checkbox',
                id: 'sonntagAn',
                value: 1,
                boxLabel: 'So.'
            }]
        },{
            xtype: 'checkboxgroup',
            id: 'abreise',
            width: 400,
            fieldLabel: 'Abreisetage',
            helpText: 'mögliche Abreisetage in der Woche',
            items: [{
                xtype: 'checkbox',
                id: 'montagAb',
                value: 1,
                boxLabel: 'Mo.'
            },{
                xtype: 'checkbox',
                id: 'dienstagAb',
                value: 1,
                boxLabel: 'Die.'
            },{
                xtype: 'checkbox',
                id: 'mittwochAb',
                value: 1,
                boxLabel: 'Mi.'
            },{
                xtype: 'checkbox',
                id: 'donnerstagAb',
                value: 1,
                boxLabel: 'Do.'
            },{
                xtype: 'checkbox',
                id: 'freitagAb',
                value: 1,
                boxLabel: 'Fr.'
            },{
                xtype: 'checkbox',
                id: 'sonnabendAb',
                value: 1,
                boxLabel: 'Sa.'
            },{
                xtype: 'checkbox',
                id: 'sonntagAb',
                value: 1,
                boxLabel: 'So.'
            }]
        },{
            xtype: 'numberfield',
            width: 50,
            fieldLabel: 'frühestens buchbar *',
            name: 'fruehestens',
            allowDecimals: false,
            allowNegative: false,
            helpText: 'frühestens buchbar, Tage vor Anreise'
        },{
            xtype: 'combo',
            width: 100,
            fieldLabel: 'spätestens buchbar *',
            helpText: 'spätestens buchbar vor Anreise',
            store: [['1','1'],['2','2'],['3','3'],['4','4'],['5','5'],['6','6'],['7','7'],['8','8'],['9','9'],['10','10']],
            name: 'spaetestens',
            hiddenName: 'spaetestens',
            editable: false,
            mode: 'local',
            typeAhead: false,
            forceSelection: true,
            triggerAction: 'all'
        },{
            xtype: 'combo',
            width: 100,
            fieldLabel: 'min. Anzahl Übernachtungen *',
            helpText: 'min. Anzahl der Übernachtungen des Kunden',
            store: [['1','1'],['2','2'],['3','3'],['4','4'],['5','5'],['6','6'],['7','7'],['8','8'],['9','9'],['10','10']],
            name: 'minimal',
            hiddenName: 'minimal',
            editable: false,
            mode: 'local',
            typeAhead: false,
            forceSelection: true,
            triggerAction: 'all'
        }];

    var formStammdaten = new Ext.form.FormPanel({
        width: 750,
        autoHeight: true,
        frame: true,
        padding: 10,
        id: 'stammdatenForm',
        border: false,
        url: '/admin/hotels/updatehotels/',
        method: 'post',
        labelWidth: 200,
        items: [
            {
                xtype: 'fieldset',
                title: 'allgemeine Angaben',
                labelWidth: 200,
                items:[allgemeineAngaben]
            },{
                xtype: 'fieldset',
                title: 'Tagesangaben',
                labelWidth: 200,
                items:[tagesAngaben]
            }
        ]
    });

    var stammdatenFenster = new Ext.Window({
        title: 'Stammdaten Hotel, Hotel ID: ' + memoryHotelId,
        width: 785,
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