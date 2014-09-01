function fillPersonaldaten(){

     if(!grid.getSelectionModel().hasSelection()){
        showMsgBox('Hotel auswählen');

        return;
    }

    if(!memoryHotelId){
        showMsgBox("Hotel auswählen");

        return;
    }

     var fensterPersonaldaten = new Ext.Window({
        title: 'Verantwortlicher Beherbergungsbetrieb, Hotel ID: ' + memoryHotelId,
        width: 940,
        autoHeight: true,
        padding: 10,
        x: 20,
        modal: true,
        shadow: false,
        resizable: false,
        closable: showCloseButton,
        border: false,
        y: 20,
        height: 400,
        buttons: [{
            text: 'Stammdaten Hotel',
            icon: '/buttons/zurueck.png',
            cls: 'x-btn-text-icon',
            iconAlign: 'left',
            handler: function(){
                fensterPersonaldaten.close();
                fillStammdaten();
            }
        },{
            text: 'Personendaten speichern',
            icon: '/buttons/vor.png',
            cls: 'x-btn-text-icon',
            iconAlign: 'right',
            tooltip: 'speichern der Personendaten',
            handler: function(){
                formHotelPersonal.getForm().submit({
                    params: {
                        hotelId: memoryHotelId
                    },
                    success: function(){
                        fensterPersonaldaten.close();
                        fillTemplate('de');
                    },
                    failure: function(){
                        showMsgBox('Bitte Eingabe überprüfen');
                    }
                });
            }
        }]
    });

    var formHotelPersonal = new Ext.form.FormPanel({
        width: 900,
        autoHeight: true,
        frame: true,
        padding: 10,
        layout: 'column',
        url: '/admin/hotels/savepersonaldata/',
        items: [{
                layout: 'form',
                labelWidth: 120,
                columnWidth: 0.5,
                border: false,
                items: [{
                    xtype: 'combo',
                    fieldLabel: 'Land *',
                    width: 150,
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    store: [[52,'Deutschland'],[174,'Österreich'],[198,'Schweiz'],[232,'Tschechien'],[205,'Slowakei']],
                    displayField: 'country',
                    valueField: 'countryId',
                    hiddenName: 'country',
                    allowBlank: false,
                    name: 'country',
                    id: 'countryCombo',
                    helpText: 'Bitte Land auswählen',
                    value: 'Deutschland',
                    editable: false,
                    typeAhead: false
                },{
                    xtype: 'combo',
                    fieldLabel: 'Bundesland',
                    width: 150,
                    store: jsonStoreRegion,
                    displayField: 'region',
                    valueField: 'region',
                    hiddenName: 'region',
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Bitte Bundesland ( Deutschland ) auswählen',
                    editable: false,
                    typeAhead: false
                },{
                    xtype: 'textfield',
                    fieldLabel: 'PLZ *',
                    width: 50,
                    maxLength: 5,
                    minLength: 4,
                    allowBlank: false,
                    id: 'zip',
                    maskRe: /^[0-9]$/,
                    helpText: 'Bitte PLZ eingeben'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Stadt *',
                    width: 150,
                    allowBlank: false,
                    store: 'jsonStoreCity',
                    displayField: 'city',
                    valueField: 'cityId',
                    hiddenName: 'cityId',
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Bitte Stadt auswählen',
                    editable: false,
                    typeAhead: false
                }]
        },{
            layout: 'form',
            columnWidth: 0.5,
            labelWidth: 120,
            border: false,
            items: [{
                    xtype: 'combo',
                    fieldLabel: 'Anrede',
                    width: 150,
                    id: 'title',
                    mode: 'local',
                    store: ['Frau','Herr'],
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Bitte Anrede auswählen',
                    editable: false,
                    typeAhead: false
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Vorname',
                    width: 250,
                    id: 'firstname',
                    minLength: 3,
                    helpText: 'Bitte Vorname eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Familienname',
                    width: 250,
                    id: 'lastname',
                    minLength: 3,
                    helpText: 'Bitte Familienname eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Strasse *',
                    allowBlank: false,
                    width: 200,
                    allowBlank: false,
                    id: 'street',
                    helpText: 'Bitte Strasse eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Mailadresse *',
                    width: 250,
                    allowBlank: false,
                    id: 'email',
                    vtype: 'email',
                    helpText: 'Bitte E-Mailadresse eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Passwort *',
                    width: 200,
                    allowBlank: false,
                    id: 'password',
                    minLength: 8,
                    helpText: 'Bitte Passwort mit min. 8 Zeichen eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Telefon',
                    width: 250,
                    id: 'phonenumber',
                    helpText: 'Bitte Telefonnummer eingeben'
                },{
                    xtype: 'textfield',
                    fieldLabel: 'Handy',
                    width: 250,
                    id: 'mobile',
                    helpText: 'Bitte Handynummer eingeben'
                },{
                    xtype: 'combo',
                    fieldLabel: 'Newsletter',
                    width: 150,
                    name: 'newsletter',
                    mode: 'local',
                    store: [[2,'aktiv'],[1,'passiv']],
                    displayField: 'newsletter',
                    valueField: 'newsletterId',
                    hiddenName: 'newsletter',
                    forceSelection: true,
                    triggerAction: 'all',
                    helpText: 'Bitte auswählen, ob ein Newsletter zugesandt wird',
                    id: 'newsletter',
                    editable: false,
                    typeAhead: false
                }
            ]
        }]
    });

    formHotelPersonal.getForm().load({
        url: '/admin/hotels/getpersonaldatahotel/',
        method: 'post',
        params: {
            hotelId: memoryHotelId
        },
        success: function(form, action){

            var variablen = Ext.util.JSON.decode(action.response.responseText);

            return;
        }
    });

    fensterPersonaldaten.add(formHotelPersonal);
    fensterPersonaldaten.doLayout();
    fensterPersonaldaten.show();




}